<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookFormat;
use App\Models\Offer;
use Illuminate\Http\Request;
use App\Models\OrderItem;
use App\Models\Wishlist;
use Illuminate\Support\Collection;

class BookApiController extends Controller
{
    /** Cached lookup of all book formats, keyed by id — loaded once per request. */
    private ?Collection $formatsCache = null;

    private function allFormats(): Collection
    {
        return $this->formatsCache ??= BookFormat::all()->keyBy('id');
    }

    private function formatObject(?int $formatId): ?array
    {
        if (! $formatId) {
            return null;
        }

        $format = $this->allFormats()->get($formatId);

        if (! $format) {
            return null;
        }

        return [
            'id'   => $format->id,
            'name' => $format->name,
            'code' => $format->code,
            'icon' => $format->icon,
        ];
    }

    public function index(Request $request)
    {
        $query = Book::with(['category', 'author.user', 'publisher.user', 'formats', 'prices.country', 'prices.currency', 'languages', 'files'])
            ->where('status', 'published')
            ->where('show_in_store', true);

        if ($request->filled('format')) {
            $formatId = $this->resolveFormatId($request->format);

            if (! $formatId) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Unknown format '{$request->format}'. Use physical, ebook, or audiobook.",
                ], 422);
            }

            $query->whereHas('formats', function ($q) use ($formatId) {
                $q->where('book_formats.id', $formatId)
                  ->where('book_book_format.is_enabled', true);
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        $sort = $request->input('sort', 'popularity');
        match ($sort) {
            'newest' => $query->latest(),
            default  => $query->orderByDesc('is_featured')->latest(),
        };

        $perPage = $request->input('per_page', 24);
        $books   = $query->paginate($perPage);

        $books->getCollection()->transform(fn ($book) => $this->attachFormatFiles($this->attachPricingSummary($book)));

        $baseFilters = fn ($q) => $q->where('status', 'published')->where('show_in_store', true);

        $newArrivalsQuery = Book::with(['category', 'author.user', 'prices.country', 'prices.currency']);
        $baseFilters($newArrivalsQuery);

        if ($request->filled('format')) {
            $formatId = $this->resolveFormatId($request->format);
            $newArrivalsQuery->whereHas('formats', function ($q) use ($formatId) {
                $q->where('book_formats.id', $formatId)
                  ->where('book_book_format.is_enabled', true);
            });
        }

        $newArrivalsLimit = (int) $request->input('new_arrivals_limit', 8);

        $newArrivals = $newArrivalsQuery->latest()->take($newArrivalsLimit)->get()
            ->map(fn ($book) => $this->attachPricingSummary($book));

        $featuredLimit = (int) $request->input('featured_limit', 8);

        $featuredBooks = $baseFilters(
                Book::with(['category', 'author.user', 'prices.country', 'prices.currency'])
            )
            ->where('is_featured', true)
            ->latest()
            ->take($featuredLimit)
            ->get()
            ->map(fn ($book) => $this->attachPricingSummary($book));

        return response()->json([
            'status'         => 'success',
            'data'           => $books,
            'new_arrivals'   => $newArrivals,
            'featured_books' => $featuredBooks,
        ]);
    }

    public function show($idOrSlug)
    {
        $book = Book::with([
                'category', 'subcategory', 'series',
                'author.user', 'publisher.user',
                'languages', 'formats',
                'prices.country', 'prices.currency', 'inventory.format',
                'shipping.methods', 'seo', 'chapters', 'galleryImages', 'files',
                'approvedReviews' => function ($q) {
                    $q->with('user:id,name')->latest()->limit(3);
                },
            ])
            ->where('status', 'published')
            ->where(function ($q) use ($idOrSlug) {
                $q->where('id', $idOrSlug)->orWhere('slug', $idOrSlug);
            })
            ->firstOrFail();

        $book = $this->attachFormatFiles($this->attachPricingSummary($book));

        $totalReviews = $book->approvedReviews()->count();
        $ratingBreakdown = [];
        for ($star = 5; $star >= 1; $star--) {
            $count = $book->approvedReviews()->where('rating', $star)->count();
            $ratingBreakdown[] = [
                'star'       => $star,
                'count'      => $count,
                'percentage' => $totalReviews > 0 ? round(($count / $totalReviews) * 100) : 0,
            ];
        }

        $book->setAttribute('rating_breakdown', $ratingBreakdown);

        // Related-book recommendations for the detail page, same pricing treatment.
        $related = $book->relatedBooks()
            ->with(['category', 'author.user', 'formats', 'prices.country', 'prices.currency'])
            ->where('status', 'published')
            ->where('show_in_store', true)
            ->get()
            ->map(fn ($b) => $this->attachFormatFiles($this->attachPricingSummary($b)));

        return response()->json([
            'status'  => 'success',
            'data'    => $book,
            'related' => $related,
        ]);
    }

    /**
     * Adds `pricing` array to the Book with book price + a resolved Offer
     * (if one is currently running for that country/format/book/category)
     * and a final `display` block telling the frontend exactly what to render.
     * Each row also carries a `book_format` object (id/name/code/icon) —
     * not just the raw `book_format_id` — so the frontend never has to
     * cross-reference the formats list to label a pricing row.
     *
     * MATCHING LOGIC per price row (each row = one country + one format):
     *   1. status = 1 (active)                         → Offer::active()
     *   2. offer's DATE window covers today             → Offer::runningNow() (date-only, time ignored)
     *   3. country_id is NULL (all countries) OR
     *      country_id matches this price row's country  → Offer::forCountry()
     *   4. target_type = 'all' OR matches this exact
     *      book_id / category_id / book_format_id       → resolveApplicableOffer()
     *
     * If multiple offers qualify, the most specific one wins:
     *   book-specific > format-specific > category-specific > all
     * and a country-specific offer beats a global one at the same level.
     */
    private function attachPricingSummary(Book $book): Book
    {
        $pricing = $book->prices->map(function ($price) use ($book) {

            $row = [
                'book_format_id'      => $price->book_format_id,
                'book_format'         => $this->formatObject($price->book_format_id), // ✅ full object, not just the id
                'country_id'          => $price->country_id,
                'country_name'        => $price->country->name ?? null,
                'currency_code'       => $price->currency->code ?? null,
                'currency_symbol'     => $price->currency->symbol ?? ($price->currency->code ?? ''),
                'price'               => (float) $price->price,
                'sale_price'          => $price->sale_price !== null ? (float) $price->sale_price : null,
                'is_on_sale'          => (bool) $price->is_on_sale,
                'has_active_discount' => $price->has_active_discount,
                'discount_percent'    => $price->discount_percent !== null ? (float) $price->discount_percent : null,
                'tax_name'            => $price->tax?->tax_name,
                'tax_rate'            => $price->tax_rate_snapshot !== null ? (float) $price->tax_rate_snapshot : null,
                'effective_price'     => $price->effective_price,
                'price_incl_tax'      => $price->price_incl_tax,
                'final_price'         => $price->final_price !== null ? (float) $price->final_price : null,
                'final_price_rounded' => $price->final_price_rounded,
                'round_off_amount'    => $price->round_off_amount !== null ? (float) $price->round_off_amount : null,
            ];

            $offer = $this->resolveApplicableOffer($book, $price->book_format_id, $price->country_id);
            $row['offer'] = null;

            $bookFinalRounded = $row['final_price_rounded'];
            $displayFinal     = $bookFinalRounded ?? (int) round($row['price']);
            $displaySource    = 'book';
            $strikePrice      = null;

            if ($offer) {
                $taxRate   = $row['tax_rate'] ?? 0;
                $offerCalc = $this->computeOfferPricing((float) $price->price, $offer, $taxRate);
                $row['offer'] = $offerCalc;

                if ($bookFinalRounded === null || $offerCalc['final_price_rounded'] < $bookFinalRounded) {
                    $displayFinal  = $offerCalc['final_price_rounded'];
                    $displaySource = 'offer';
                    $strikePrice   = $bookFinalRounded ?? (int) round($row['price']);
                } elseif ($offerCalc['final_price_rounded'] > $bookFinalRounded) {
                    $strikePrice = null;
                }
            } elseif ($row['has_active_discount'] && $bookFinalRounded !== null) {
                $taxRate = $row['tax_rate'] ?? 0;
                $regularFinal = (int) round($row['price'] + ($row['price'] * $taxRate / 100));
                if ($regularFinal > $bookFinalRounded) {
                    $strikePrice = $regularFinal;
                }
            }

            $savings = $strikePrice ? max(0, $strikePrice - $displayFinal) : 0;
            $discountPercentDisplay = ($strikePrice && $strikePrice > 0)
                ? (int) round(($savings / $strikePrice) * 100)
                : 0;

         $preTaxAmount = ($displaySource === 'offer' && $row['offer'])
    ? $row['offer']['offer_price']
    : $row['effective_price'];

$row['display'] = [
    'source'                 => $displaySource,
    'final_price'            => $displayFinal,
    'strike_price'           => $strikePrice,
    'savings_amount'         => $savings,
    'discount_percent_label' => ($displaySource === 'offer' && $row['offer'])
        ? (int) round($row['offer']['discount_value'])
        : $discountPercentDisplay,
    'tax_amount' => round($displayFinal - $preTaxAmount, 2), // ✅ NEW
    'tax_rate'   => $row['tax_rate'] ?? 0,                    // ✅ NEW
];

return $row;
        })->values();

        $book->setAttribute('pricing', $pricing);

        return $book;
    }

    private function resolveApplicableOffer(Book $book, int $formatId, ?int $countryId): ?Offer
    {
        $candidates = Offer::active()
            ->runningNow()
            ->forCountry($countryId)
            ->where(function ($q) use ($book, $formatId) {
                $q->where('target_type', 'all')
                  ->orWhere(function ($q2) use ($book) {
                      $q2->where('target_type', 'book')->where('book_id', $book->id);
                  })
                  ->orWhere(function ($q2) use ($book) {
                      $q2->where('target_type', 'category')->where('category_id', $book->category_id);
                  })
                  ->orWhere(function ($q2) use ($formatId) {
                      $q2->where('target_type', 'format')->where('book_format_id', $formatId);
                  });
            })
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        $specificity = ['book' => 4, 'format' => 3, 'category' => 2, 'all' => 1];

        return $candidates->sort(function ($a, $b) use ($specificity) {
            $aCountryScore = $a->country_id ? 1 : 0;
            $bCountryScore = $b->country_id ? 1 : 0;
            if ($aCountryScore !== $bCountryScore) {
                return $bCountryScore <=> $aCountryScore;
            }
            return ($specificity[$b->target_type] ?? 0) <=> ($specificity[$a->target_type] ?? 0);
        })->first();
    }

    private function computeOfferPricing(float $basePrice, Offer $offer, float $taxRate): array
    {
        $discountAmount = $offer->discount_type === 'percentage'
            ? $basePrice * ((float) $offer->discount_value / 100)
            : (float) $offer->discount_value;

        $discountAmount = min($discountAmount, $basePrice);
        $offerPrice     = round($basePrice - $discountAmount, 2, PHP_ROUND_HALF_UP);

        $finalPrice        = round($offerPrice + ($offerPrice * $taxRate / 100), 2, PHP_ROUND_HALF_UP);
        $finalPriceRounded = (int) round($finalPrice, 0, PHP_ROUND_HALF_UP);
        $roundOffAmount    = round($finalPriceRounded - $finalPrice, 2, PHP_ROUND_HALF_UP);

        return [
            'offer_id'            => $offer->id,
            'offer_title'         => $offer->title,
            'discount_type'       => $offer->discount_type,
            'discount_value'      => (float) $offer->discount_value,
            'discount_amount'     => round($discountAmount, 2, PHP_ROUND_HALF_UP),
            'offer_price'         => $offerPrice,
            'final_price'         => $finalPrice,
            'final_price_rounded' => $finalPriceRounded,
            'round_off_amount'    => $roundOffAmount,
            'ends_at'             => optional($offer->ends_at)->toIso8601String(),
        ];
    }

    /**
     * Nests each BookFile under its matching format (matched on book_formats.id,
     * since storeFormats()/storeFiles() key everything by the catalog format id,
     * not the book_book_format pivot id). Also appends convenience URL fields
     * (ebook_file_url, audio_sample_url), and nests each format's own pricing
     * rows (one per country) so the frontend can read pricing directly from a
     * specific format without filtering the flat book-level pricing array.
     */
    private function attachFormatFiles(Book $book): Book
    {
        $bookPricing = $book->pricing ?? collect();

        $book->formats->each(function ($format) use ($book, $bookPricing) {
            $formatFiles = $book->files->where('book_format_id', $format->id)->values();

            $format->setAttribute('files', $formatFiles);

            $ebookFile = $formatFiles->firstWhere('file_type', 'epub')
                ?? $formatFiles->firstWhere('file_type', 'pdf');

            $audioFile = $formatFiles->firstWhere('file_type', 'sample_audio');

            $format->setAttribute('ebook_file_url', $ebookFile ? asset($ebookFile->file_path) : null);
            $format->setAttribute('audio_sample_url', $audioFile ? asset($audioFile->file_path) : null);

            $formatPricing = $bookPricing->where('book_format_id', $format->id)->values();
            $format->setAttribute('pricing', $formatPricing);
        });

        $book->unsetRelation('files');

        return $book;
    }

    private function resolveFormatId(string $identifier): ?int
    {
        $code = strtolower(trim($identifier));

        $format = BookFormat::whereRaw('LOWER(code) = ?', [$code])
            ->orWhereRaw('LOWER(name) = ?', [$code])
            ->first();

        return $format?->id;
    }

    public function stats()
    {
        $formats = BookFormat::active()->ordered()->get(['id', 'name', 'code']);

        $counts = [];
        foreach ($formats as $format) {
            $counts[] = [
                'format_id' => $format->id,
                'name'      => $format->name,
                'code'      => $format->code,
                'total'     => Book::where('status', 'published')
                                    ->where('show_in_store', true)
                                    ->whereHas('formats', function ($q) use ($format) {
                                        $q->where('book_formats.id', $format->id)
                                          ->where('book_book_format.is_enabled', true);
                                    })
                                    ->count(),
            ];
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'total_published' => Book::where('status', 'published')->where('show_in_store', true)->count(),
                'by_format'        => $counts,
            ],
        ]);
    }

    public function formats()
    {
        return response()->json([
            'status' => 'success',
            'data'   => BookFormat::active()->ordered()->get(['id', 'name', 'code', 'icon']),
        ]);
    }

    public function recommended(Request $request)
    {
        $limit = (int) $request->get('limit', 10);
        $user  = auth('sanctum')->user();

        $ebookFormatId = BookFormat::where('code', 'ebook')->value('id');

        $baseQuery = function () use ($ebookFormatId) {
            return Book::query()
                ->with(['prices.country', 'prices.currency', 'formats', 'files', 'category'])
                ->published()
                ->where('show_in_store', true)
                ->when($ebookFormatId, function ($q) use ($ebookFormatId) {
                    $q->whereHas('formats', function ($fq) use ($ebookFormatId) {
                        $fq->where('book_formats.id', $ebookFormatId)
                           ->where('book_book_format.is_enabled', true);
                    });
                });
        };

        $excludeIds    = [];
        $categoryBased = collect();
        $purchaseBased = collect();

        if ($user) {
            $purchasedBookIds = OrderItem::whereHas('order', function ($q) use ($user) {
                    $q->where('user_id', $user->id)->where('payment_status', 'paid');
                })
                ->pluck('book_id')->unique()->values();

            $wishlistBookIds = Wishlist::where('user_id', $user->id)
                ->pluck('book_id')->unique()->values();

            $excludeIds = $purchasedBookIds->merge($wishlistBookIds)->unique()->values()->all();

            $categoryIds = Book::whereIn('id', $excludeIds)->pluck('category_id')->filter()->unique();
            if ($categoryIds->isNotEmpty()) {
                $categoryBased = $baseQuery()
                    ->whereIn('category_id', $categoryIds)
                    ->whereNotIn('id', $excludeIds)
                    ->orderByDesc('id')
                    ->limit($limit * 2)
                    ->get()
                    ->sortByDesc('average_rating')
                    ->take($limit)
                    ->values()
                    ->map(fn ($book) => $this->attachFormatFiles($this->attachPricingSummary($book)));
            }

            if ($purchasedBookIds->isNotEmpty()) {
                $purchaseCategoryIds = Book::whereIn('id', $purchasedBookIds)->pluck('category_id')->filter()->unique();
                $purchaseBased = $baseQuery()
                    ->whereIn('category_id', $purchaseCategoryIds)
                    ->whereNotIn('id', $excludeIds)
                    ->limit($limit * 2)
                    ->get()
                    ->sortByDesc('average_rating')
                    ->take($limit)
                    ->values()
                    ->map(fn ($book) => $this->attachFormatFiles($this->attachPricingSummary($book)));
            }
        } else {
            // ✅ NEW: guests previously got two empty arrays with no fallback.
            // Give them a generic "popular in each ebook-enabled category" list instead.
            $categoryBased = $baseQuery()
                ->orderByDesc('is_featured')
                ->latest()
                ->limit($limit)
                ->get()
                ->map(fn ($book) => $this->attachFormatFiles($this->attachPricingSummary($book)));
        }

        return response()->json([
            'success' => true,
            'data' => [
                'based_on_category' => $categoryBased,
                'based_on_purchase' => $purchaseBased,
            ],
        ]);
    }
}
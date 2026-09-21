<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookFormat;
use App\Models\Offer;
use App\Models\Series;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class SeriesApiController extends Controller
{
    /** Cached lookup of all book formats, keyed by id — loaded once per request. */
    private ?Collection $formatsCache = null;

    /**
     * GET /api/series
     * Active series list — lightweight, just id/name/cover + book count.
     */
    public function index(): JsonResponse
    {
        $series = Series::query()
            ->active()
            ->withCount(['books' => fn ($q) => $q->where('status', 'published')])
            ->ordered()
            ->get()
            ->map(fn (Series $s) => [
                'id'          => $s->id,
                'name'        => $s->name,
                'slug'        => $s->slug,
                'cover_image' => $s->cover_image_url,
                'books_count' => $s->books_count,
            ]);

        return response()->json([
            'status' => 'success',
            'data'   => $series,
        ]);
    }

    /**
     * GET /api/series/{idOrSlug}?format=ebook&country_id=1
     *
     * Series detail with its books — each book carries the full formats +
     * country-based pricing block (same shape as /api/books/{id}).
     *
     * Query params:
     *   format      optional: physical | ebook | audiobook
     *               → only books with that format enabled are returned
     *   country_id  optional: only include pricing rows for this country
     */
    public function show(Request $request, string $idOrSlug): JsonResponse
    {
        $series = Series::query()
            ->active()
            ->where(fn ($q) => $q->where('id', $idOrSlug)->orWhere('slug', $idOrSlug))
            ->first();

        if (! $series) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Series not found',
            ], 404);
        }

        $formatCode = $request->query('format');
        $countryId  = $request->filled('country_id') ? (int) $request->query('country_id') : null;

        $formatId = null;
        if ($formatCode) {
            $formatId = $this->resolveFormatId($formatCode);
            if (! $formatId) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Unknown format '{$formatCode}'. Use physical, ebook, or audiobook.",
                ], 422);
            }
        }

        $booksQuery = $series->books()
            ->with([
                'category', 'author.user', 'publisher.user',
                'formats', 'prices.country', 'prices.currency',
                'languages', 'files',
            ])
            ->where('status', 'published')
            ->where('show_in_store', true);

        if ($formatId) {
            $booksQuery->whereHas('formats', function ($q) use ($formatId) {
                $q->where('book_formats.id', $formatId)
                  ->where('book_book_format.is_enabled', true);
            });
        }

        $books = $booksQuery->get()->map(function ($book) use ($countryId) {
            $book = $this->attachPricingSummary($book, $countryId);
            $book = $this->attachFormatFiles($book);
            return $book;
        });

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'            => $series->id,
                'name'          => $series->name,
                'slug'          => $series->slug,
                'description'   => $series->description,
                'cover_image'   => $series->cover_image_url,
                'publisher'     => $series->publisher?->name,
                'format_filter' => $formatCode,
                'country_id'    => $countryId,
                'books_count'   => $books->count(),
                'books'         => $books,
            ],
        ]);
    }

    /* ───────────────────────── Pricing helpers ───────────────────────── */

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

    /**
     * Adds `pricing` array to the Book, optionally scoped to one country_id,
     * with active-offer resolution and a `display` block for the frontend.
     */
    private function attachPricingSummary(Book $book, ?int $onlyCountryId = null): Book
    {
        $prices = $onlyCountryId
            ? $book->prices->where('country_id', $onlyCountryId)
            : $book->prices;

        $pricing = $prices->map(function ($price) use ($book) {

            $row = [
                'book_format_id'      => $price->book_format_id,
                'book_format'         => $this->formatObject($price->book_format_id),
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
                'tax_amount' => round($displayFinal - $preTaxAmount, 2),
                'tax_rate'   => $row['tax_rate'] ?? 0,
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
     * Nests each BookFile under its matching format, adds convenience URL
     * fields (ebook_file_url, audio_sample_url), and nests each format's
     * own pricing rows.
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
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuthorProfile;
use App\Models\Book;
use App\Models\BookFormat;
use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class HomeApiController extends Controller
{
    /** Cached lookup of all book formats, keyed by id — loaded once per request. */
    private ?Collection $formatsCache = null;

    /* ─────────────────────────────────────────────
     | GET /api/home
     | One call for the whole homepage: Stats,
     | New Arrivals, Featured Books, Never Miss To Read.
     | Each book now carries the same offer-based
     | pricing block used on the book listing/detail API.
     ───────────────────────────────────────────── */
    public function index(Request $request)
    {
        $newArrivalsLimit = (int) $request->input('new_arrivals_limit', 6);
        $featuredLimit    = (int) $request->input('featured_limit', 6);
        $neverMissLimit   = (int) $request->input('never_miss_limit', 8);

        // ✅ NEW: eager-load prices.country / prices.currency so
        // attachPricingSummary() can resolve offers + display pricing,
        // same relations BookApiController loads.
        $base = fn () => Book::with(['category', 'author.user', 'prices.country', 'prices.currency'])
            ->where('status', 'published')
            ->where('show_in_store', true);

        $newArrivals = $base()
            ->latest()
            ->take($newArrivalsLimit)
            ->get()
            ->map(fn ($b) => $this->formatBook($b));

        $featuredBooks = $base()
            ->where('is_featured', true)
            ->latest()
            ->take($featuredLimit)
            ->get()
            ->map(fn ($b) => $this->formatBook($b));

        $neverMissToRead = $base()
            ->where('is_never_miss_to_read', true)
            ->latest()
            ->take($neverMissLimit)
            ->get()
            ->map(fn ($b) => $this->formatBook($b));

        return response()->json([
            'status' => 'success',
            'data'   => [
                'stats'              => $this->buildStats(),
                'new_arrivals'       => $newArrivals,
                'featured_books'     => $featuredBooks,
                'never_miss_to_read' => $neverMissToRead,
            ],
        ]);
    }

    /* ─────────────────────────────────────────────
     | Stats strip: "1,000+ Published Books", etc.
     | published_books / ebooks / authors -> live DB counts
     | years_of_heritage / branch_libraries -> fixed, from config/site_stats.php
     ───────────────────────────────────────────── */
    private function buildStats(): array
    {
        $publishedBooksCount = Book::where('status', 'published')
            ->where('show_in_store', true)
            ->count();

        // "E-Books" = published books that have the eBook format enabled
        $ebooksCount = Book::where('status', 'published')
            ->where('show_in_store', true)
            ->whereHas('formats', function ($q) {
                $q->where(function ($q2) {
                    $q2->where('book_formats.code', 'ebook')
                       ->orWhere('book_formats.name', 'like', '%ebook%')
                       ->orWhere('book_formats.name', 'like', '%e-book%');
                })->where('book_book_format.is_enabled', true);
            })
            ->count();

        $authorsCount = AuthorProfile::active()->count();

        return [
            'published_books'   => $publishedBooksCount,
            'years_of_heritage'  => 10,
            'ebooks'             => $ebooksCount,
            'authors'            => $authorsCount,
            'branch_libraries'   => 20,
        ];
    }

    /* ─────────────────────────────────────────────
     | Shared book card shape
     | Now includes the same `pricing` array + resolved
     | offer/`display` block as the Book API, so New
     | Arrivals / Featured / Never Miss To Read all show
     | live offer pricing instead of the raw book price.
     ───────────────────────────────────────────── */
    private function formatBook(Book $book): array
    {
        $book = $this->attachPricingSummary($book);

        /** @var Collection $pricing */
        $pricing = $book->pricing;
        $primary = $pricing->first(); // e.g. the book's default/global price row

        return [
            'id'                     => $book->id,
            'slug'                   => $book->slug,
            'title'                  => $book->title,
            'subtitle'               => $book->subtitle,
            'cover_image'            => $book->cover_image_url,
            'author'                 => $book->author?->user?->name,
            'category'               => $book->category?->name_en,
            // kept for backward compatibility with existing home-screen widgets
            'price'                  => $primary['price'] ?? null,
            'sale_price'             => $primary['sale_price'] ?? null,
            'is_featured'            => (bool) $book->is_featured,
            'is_bestseller'          => (bool) $book->is_bestseller,
            'is_never_miss_to_read'  => (bool) $book->is_never_miss_to_read,
            // ✅ NEW: full offer-aware pricing, one row per country/format,
            // each with its own `offer` + `display` block — identical shape
            // to what BookApiController returns.
            'pricing'                => $pricing,
        ];
    }

    /* ─────────────────────────────────────────────
     | Offer/pricing pipeline — copied 1:1 from
     | BookApiController so home + listing + detail
     | all resolve offers the exact same way.
     ───────────────────────────────────────────── */

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
     * Adds `pricing` array to the Book with book price + a resolved Offer
     * (if one is currently running for that country/format/book/category)
     * and a final `display` block telling the frontend exactly what to render.
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

    private function resolveApplicableOffer(Book $book, ?int $formatId, ?int $countryId): ?Offer
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
}
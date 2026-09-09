<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuthorProfile;
use App\Models\Book;
use Illuminate\Http\Request;

class HomeApiController extends Controller
{
    /* ─────────────────────────────────────────────
     | GET /api/home
     | One call for the whole homepage: Stats,
     | New Arrivals, Featured Books, Never Miss To Read.
     ───────────────────────────────────────────── */
    public function index(Request $request)
    {
        $newArrivalsLimit = (int) $request->input('new_arrivals_limit', 6);
        $featuredLimit    = (int) $request->input('featured_limit', 6);
        $neverMissLimit   = (int) $request->input('never_miss_limit', 8);

        $base = fn () => Book::with(['category', 'author.user', 'prices'])
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
            'years_of_heritage'  => config('site_stats.years_of_heritage'),
            'ebooks'             => $ebooksCount,
            'authors'            => $authorsCount,
            'branch_libraries'   => config('site_stats.branch_libraries'),
        ];
    }

    /* ─────────────────────────────────────────────
     | Shared book card shape
     ───────────────────────────────────────────── */
    private function formatBook(Book $book): array
    {
        $price = $book->prices->first();

        return [
            'id'                     => $book->id,
            'slug'                   => $book->slug,
            'title'                  => $book->title,
            'subtitle'               => $book->subtitle,
            'cover_image'            => $book->cover_image_url,
            'author'                 => $book->author?->user?->name,
            'category'               => $book->category?->name_en,
            'price'                  => $price?->price,
            'sale_price'             => $price?->sale_price,
            'is_featured'            => (bool) $book->is_featured,
            'is_bestseller'          => (bool) $book->is_bestseller,
            'is_never_miss_to_read'  => (bool) $book->is_never_miss_to_read,
        ];
    }
}
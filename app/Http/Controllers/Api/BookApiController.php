<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookFormat;
use Illuminate\Http\Request;

class BookApiController extends Controller
{
    public function index(Request $request)
    {
        $query = Book::with(['category', 'author.user', 'publisher.user', 'formats', 'prices', 'languages', 'files'])
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

        // ✅ nest files under each format for every book in the paginated list
        $books->getCollection()->transform(fn ($book) => $this->attachFormatFiles($book));

        // Base filters shared by the extra sections below
        $baseFilters = fn ($q) => $q->where('status', 'published')->where('show_in_store', true);

        // Optional: keep new_arrivals scoped to the same format filter, if one was passed
        $newArrivalsQuery = Book::with(['category', 'author.user', 'prices']);
        $baseFilters($newArrivalsQuery);

        if ($request->filled('format')) {
            $formatId = $this->resolveFormatId($request->format);
            $newArrivalsQuery->whereHas('formats', function ($q) use ($formatId) {
                $q->where('book_formats.id', $formatId)
                  ->where('book_book_format.is_enabled', true);
            });
        }

        $newArrivalsLimit = (int) $request->input('new_arrivals_limit', 8); // default 8, pass ?new_arrivals_limit=6 if you want 6

        $newArrivals = $newArrivalsQuery->latest()->take($newArrivalsLimit)->get();

        $featuredLimit = (int) $request->input('featured_limit', 8);

        $featuredBooks = $baseFilters(
                Book::with(['category', 'author.user', 'prices'])
            )
            ->where('is_featured', true)
            ->latest()
            ->take($featuredLimit)
            ->get();

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
                'prices.country', 'inventory.format',
                'shipping.methods', 'seo', 'chapters', 'galleryImages', 'files',
            ])
            ->where('status', 'published')
            ->where(function ($q) use ($idOrSlug) {
                $q->where('id', $idOrSlug)->orWhere('slug', $idOrSlug);
            })
            ->firstOrFail();

        // ✅ nest files under each format
        $book = $this->attachFormatFiles($book);

        return response()->json(['status' => 'success', 'data' => $book]);
    }

    /**
     * Nests each BookFile under its matching format (matched on book_formats.id,
     * since storeFormats()/storeFiles() key everything by the catalog format id,
     * not the book_book_format pivot id). Also appends convenience URL fields
     * (ebook_file_url, audio_sample_url) directly on each format for easy frontend access.
     */
    private function attachFormatFiles(Book $book): Book
    {
        $book->formats->each(function ($format) use ($book) {
            $formatFiles = $book->files->where('book_format_id', $format->id)->values();

            $format->setAttribute('files', $formatFiles);

            $ebookFile = $formatFiles->firstWhere('file_type', 'epub')
                ?? $formatFiles->firstWhere('file_type', 'pdf');

            $audioFile = $formatFiles->firstWhere('file_type', 'sample_audio');

            $format->setAttribute('ebook_file_url', $ebookFile ? asset($ebookFile->file_path) : null);
            $format->setAttribute('audio_sample_url', $audioFile ? asset($audioFile->file_path) : null);
        });

        // remove the flat book-level files list now that it's nested per-format
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
}
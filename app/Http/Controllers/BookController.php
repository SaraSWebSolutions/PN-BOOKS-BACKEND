<?php

namespace App\Http\Controllers;

use App\Models\AuthorProfile;
use App\Models\Book;
use App\Models\BookChapter;
use App\Models\BookFile;
use App\Models\BookFormat;
use App\Models\BookInventory;
use App\Models\BookPrice;
use App\Models\BookSeo;
use App\Models\BookShipping;
use App\Models\BookShippingMethod;
use App\Models\Category;
use App\Models\Country;
use App\Models\Language;
use App\Models\BookGalleryImage;
use App\Models\PublisherProfile;
use App\Models\Series;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\Process;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use App\Models\Offer;

class BookController extends Controller
{
    /* ───────────────────────── Ownership / scoping helpers ─────────────────────────
     |
     | "admin" sees and can touch everything.
     | "author" / "publisher" only see and can touch books where created_by == their own id.
     | This is enforced in TWO places:
     |   1) index() — the list itself is scoped, so they never even see other people's books.
     |   2) authorizeOwner($book) — a guard called at the top of every tab-save / action
     |      method, so a user can't bypass the UI by editing the book id in the URL/form.
     |
     */

    /**
     * The ONLY special role that matters here. Everyone who is not admin
     * is treated identically — a plain "owner" — regardless of what their
     * role is actually called (author, publisher, or anything added later).
     */
    protected function isAdmin(): bool
    {
        return Auth::user()->hasRole('admin');
    }

    /**
     * Restrict a Book query to only what the current user is allowed to see.
     * Admin -> untouched (sees every book).
     * Anyone else -> only books where created_by == their own id.
     */
    protected function scopeToOwner($query)
    {
        if ($this->isAdmin()) {
            return $query;
        }

        return $query->where('created_by', Auth::id());
    }

    /**
     * Call at the top of any single-book action. Aborts 403 if the current
     * user isn't admin and isn't the one who created this book. This is what
     * stops someone from bypassing the list by editing the URL directly.
     */
    protected function authorizeOwner(Book $book): void
    {
        if ($this->isAdmin()) {
            return;
        }

        if ($book->created_by !== Auth::id()) {
            abort(403, 'You do not have permission to access this book.');
        }
    }

    /* ───────────────────────── Listing ───────────────────────── */

   public function index()
{
    $books = $this->scopeToOwner(
        Book::with(['category', 'author.user', 'publisher.user', 'formats', 'prices'])
    )->latest()->get();

    $statsQuery = fn () => $this->scopeToOwner(Book::query());

    $stats = [
        'total'     => $statsQuery()->count(),
        'published' => $statsQuery()->where('status', 'published')->count(),
        'draft'     => $statsQuery()->where('status', 'draft')->count(),
        'featured'  => $statsQuery()->where('is_featured', true)->count(),
    ];

    // ✅ NEW — for the Category / Author filter dropdowns
    $categories = Category::active()->ordered()->get();
    $authors    = AuthorProfile::with('user')->active()->get();

    return view('books.index', compact('books', 'stats', 'categories', 'authors'));
}
    public function create()
    {
        return view('books.create', [
            'book'        => null,
            // Same dropdowns for everyone. Who can see/edit the resulting book
            // afterwards is handled purely by created_by, not by what gets picked here.
            'authors'     => AuthorProfile::with('user')->active()->get(),
            'publishers'  => PublisherProfile::with('user')->where('status', true)->get(),
            'categories'  => Category::active()->ordered()->get(),
            'formats'     => BookFormat::active()->ordered()->get(),
            'languages'   => Language::active()->ordered()->get(),
           'countries' => Country::with('activeTax')->get(),
        ]);
    }

    public function edit(Book $book)
    {
        $this->authorizeOwner($book);

        // ✅ Added 'files' and 'chapters' so Files & DRM tab can show existing uploads
        $book->load(
            'languages',
            'formats',
            'category',
            'subcategory',
            'series',
            'prices',
            'inventory',
            'shipping.methods',
            'seo',
            'files',
            'chapters', 'galleryImages'
        );

        return view('books.edit', [
            'book'        => $book,
            'authors'     => AuthorProfile::with('user')->active()->get(),
            'publishers'  => PublisherProfile::with('user')->where('status', true)->get(),
            'categories'  => Category::active()->ordered()->get(),
            'formats'     => BookFormat::active()->ordered()->get(),
            'languages'   => Language::active()->ordered()->get(),
            'countries' => Country::with('activeTax')->get(),
        ]);
    }

   // ✅ Book detail / preview page
public function show(Book $book)
{
    $this->authorizeOwner($book);

    $book->load(
        'category',
        'subcategory',
        'series',
        'author.user',
        'publisher.user',
        'languages',
        'formats',
        'prices.country',
        'prices.currency',
        'prices.tax',              // ✅ needed for tax_name in pricing table
        'inventory.format',
        'shipping.methods',
        'seo',
        'files.format',
        'chapters',
        'galleryImages',
        'approvedReviews.user',
        'relatedBooks'
    );

    // ✅ NEW: resolve tax + offer for every price row so the Pricing table
    // can show which offer (if any) is currently applied, same logic as the storefront API.
    $this->attachOfferInfoToPrices($book);

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

    return view('books.show', compact('book'));
}

/**
 * Attaches ->offer and ->display to each BookPrice in $book->prices,
 * using the exact same matching + specificity logic as BookApiController.
 * This lets the admin preview page show "which offer is currently winning"
 * for each format/country row, not just the raw price + tax.
 */
private function attachOfferInfoToPrices(Book $book): void
{
    $book->prices->each(function ($price) use ($book) {
        $offer = $this->resolveApplicableOffer($book, $price->book_format_id, $price->country_id);
        $price->setAttribute('offer', null);

        $bookFinalRounded = $price->final_price_rounded;
        $displayFinal     = $bookFinalRounded ?? (int) round((float) $price->price);
        $displaySource    = 'book';
        $strikePrice       = null;

        if ($offer) {
            $taxRate   = (float) ($price->tax_rate_snapshot ?? 0);
            $offerCalc = $this->computeOfferPricing((float) $price->price, $offer, $taxRate);
            $price->setAttribute('offer', $offerCalc);

            if ($bookFinalRounded === null || $offerCalc['final_price_rounded'] < $bookFinalRounded) {
                $displayFinal  = $offerCalc['final_price_rounded'];
                $displaySource = 'offer';
                $strikePrice   = $bookFinalRounded ?? (int) round((float) $price->price);
            }
        }

        $price->setAttribute('display', [
            'source'       => $displaySource,
            'final_price'  => $displayFinal,
            'strike_price' => $strikePrice,
        ]);
    });
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

    return [
        'offer_id'            => $offer->id,
        'offer_title'         => $offer->title,
        'discount_type'       => $offer->discount_type,
        'discount_value'      => (float) $offer->discount_value,
        'discount_amount'     => round($discountAmount, 2, PHP_ROUND_HALF_UP),
        'offer_price'         => $offerPrice,
        'final_price'         => $finalPrice,
        'final_price_rounded' => $finalPriceRounded,
        'ends_at'             => optional($offer->ends_at)->toIso8601String(),
    ];
}







    public function destroy(Book $book)
    {
        $this->authorizeOwner($book);

        try {
            $book->delete();
            return response()->json(['status' => 'success', 'message' => "Book '{$book->title}' deleted."]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function toggleFeatured(Book $book)
    {
        $this->authorizeOwner($book);

        $book->is_featured = ! $book->is_featured;
        $book->save();

        return response()->json([
            'status'  => 'success',
            'message' => $book->is_featured ? "'{$book->title}' marked as featured." : "'{$book->title}' removed from featured.",
        ]);
    }

    public function subcategoriesByCategory(Category $category)
    {
        $subcategories = $category->subcategories()
            ->active()
            ->ordered()
            ->get(['id', 'name_en', 'name_ms']);

        return response()->json(
            $subcategories->map(fn ($s) => [
                'id'   => $s->id,
                'name' => $s->name,
            ])
        );
    }

    public function seriesByPublisher(PublisherProfile $publisher)
    {
        return response()->json(
            Series::where('publisher_id', $publisher->user_id)
                ->where('is_active', 1)
                ->orderBy('name')
                ->get(['id', 'name'])
        );
    }

    /* ───────────────────────── Tab 1: Basic Info ───────────────────────── */

    public function storeBasic_10_9_26(Request $request)
    {
        $data = $request->validate([
            'book_id'            => 'nullable|exists:books,id',
            'category_id'        => 'required|exists:categories,id',
            'subcategory_id'     => 'nullable|exists:subcategories,id',
            'series_id'          => 'nullable|exists:series,id',
            'author_id'          => 'required|exists:author_profiles,id',
            'publisher_id'       => 'required|exists:publisher_profiles,id',
            'title'              => 'required|string|max:200',
            'subtitle'           => 'nullable|string|max:200',
            'isbn'               => 'required|string|max:20',
            'languages'          => 'required|array|min:1',
            'languages.*'        => 'exists:languages,id',
            'short_description'  => 'nullable|string|max:160',
            'description'        => 'required|string',
            'cover_image'        => 'nullable|image|mimes:jpg,jpeg,png,webp',
        ]);

        try {
            $book = ! empty($data['book_id']) ? Book::findOrFail($data['book_id']) : new Book();

            // ✅ Ownership check for edits — a non-admin can only touch a book they created.
            if ($book->exists) {
                $this->authorizeOwner($book);
            }

            $book->fill([
                'category_id'        => $data['category_id'],
                'subcategory_id'     => $data['subcategory_id'] ?? null,
                'series_id'          => $data['series_id'] ?? null,
                'author_id'          => $data['author_id'],
                'publisher_id'       => $data['publisher_id'],
                'title'              => trim($data['title']),
                'subtitle'           => $data['subtitle'] ?? null,
                'isbn'               => $data['isbn'] ?? null,
                'short_description'  => $data['short_description'] ?? null,
                'description'        => $data['description'] ?? null,
            ]);

            if (! $book->exists) {
                $book->status     = 'draft';
                $book->created_by = Auth::id(); // ✅ this is the line that ties the book to whoever is logged in
            }
            $book->updated_by = Auth::id();

            if ($request->hasFile('cover_image')) {
                $book->cover_image = $this->storeUpload($request->file('cover_image'), 'books');
            }

            $book->save();
            $book->languages()->sync($data['languages']);

            return response()->json([
                'status'  => 'success',
                'message' => 'Basic info saved',
                'book_id' => $book->id,
            ]);
        } catch (HttpException $e) {
            throw $e; // let 403s from authorizeOwner() propagate as-is
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function storeBasic(Request $request)
{
    $data = $request->validate([
        'book_id'            => 'nullable|exists:books,id',
        'category_id'        => 'required|exists:categories,id',
        'subcategory_id'     => 'nullable|exists:subcategories,id',
        'series_id'          => 'nullable|exists:series,id',
        'author_id'          => 'required|exists:author_profiles,id',
        'publisher_id'       => 'required|exists:publisher_profiles,id',
        'title'              => 'required|string|max:200',
        'subtitle'           => 'nullable|string|max:200',
        'isbn'               => [
            'required',
            'string',
            'max:20',
            Rule::unique('books', 'isbn')
                ->ignore($request->book_id)
                ->withoutTrashed(),
        ],
        'languages'          => 'required|array|min:1',
        'languages.*'        => 'exists:languages,id',
        'short_description'  => 'nullable|string|max:160',
        'description'        => 'required|string',
        'cover_image'        => 'nullable|image|mimes:jpg,jpeg,png,webp',
    ], [
        'isbn.unique' => 'This ISBN is already used by another book. Please enter a unique ISBN.',
    ]);

    try {
        $book = ! empty($data['book_id']) ? Book::findOrFail($data['book_id']) : new Book();

        // ✅ Ownership check for edits — a non-admin can only touch a book they created.
        if ($book->exists) {
            $this->authorizeOwner($book);
        }

        $book->fill([
            'category_id'        => $data['category_id'],
            'subcategory_id'     => $data['subcategory_id'] ?? null,
            'series_id'          => $data['series_id'] ?? null,
            'author_id'          => $data['author_id'],
            'publisher_id'       => $data['publisher_id'],
            'title'              => trim($data['title']),
            'subtitle'           => $data['subtitle'] ?? null,
            'isbn'               => $data['isbn'] ?? null,
            'short_description'  => $data['short_description'] ?? null,
            'description'        => $data['description'] ?? null,
        ]);

        if (! $book->exists) {
            $book->status     = 'draft';
            $book->created_by = Auth::id(); // ✅ this is the line that ties the book to whoever is logged in
        }
        $book->updated_by = Auth::id();

        if ($request->hasFile('cover_image')) {
            $book->cover_image = $this->storeUpload($request->file('cover_image'), 'books');
        }

        $book->save();
        $book->languages()->sync($data['languages']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Basic info saved',
            'book_id' => $book->id,
        ]);
    } catch (HttpException $e) {
        throw $e; // let 403s from authorizeOwner() propagate as-is
    } catch (\Throwable $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
    }
}

    /* ───────────────────────── Tab 2: Formats ───────────────────────── */

    public function storeFormats(Request $request, Book $book)
    {
        $this->authorizeOwner($book);

        $data = $request->validate([
            'formats'              => 'required|array|min:1',
            'formats.*'            => 'exists:book_formats,id',
            'sku'                  => 'nullable|array',
            'sku.*'                => 'nullable|string|max:100',
            'settings'             => 'nullable|array',
            'settings.*'           => 'nullable|array',

            'settings.*.edition'            => 'nullable|string|max:100',
            'settings.*.pages'              => 'nullable|integer|min:1',
            'settings.*.publication_date'   => 'nullable|date',
            'settings.*.height'             => 'nullable|numeric|min:0',
            'settings.*.width'              => 'nullable|numeric|min:0',
            'settings.*.thickness'          => 'nullable|numeric|min:0',
            'settings.*.weight'             => 'nullable|numeric|min:0',
            'settings.*.isbn_print'         => 'nullable|string|max:20',

            'settings.*.ebook_format'       => 'nullable|string|in:epub_pdf,epub,pdf',
            'settings.*.drm'                => 'nullable|string|in:lcp,watermark,none',
            'settings.*.reader_access'      => 'nullable|array',
            'settings.*.reader_access.*'    => 'string|in:web,android,ios',
            'settings.*.download_option'    => 'nullable|string|in:reader_only,downloadable',
            'settings.*.download_limit'     => 'nullable|integer|min:0',
            'settings.*.access_period'      => 'nullable|string|in:unlimited,1_year,6_months',

            'settings.*.narrator'           => 'nullable|string|max:150',
            'settings.*.total_duration'     => 'nullable|string|max:20',
            'settings.*.audio_format'       => 'nullable|string|in:mp3,m4b',
        ]);

        try {
            $sync = [];
            foreach ($data['formats'] as $formatId) {
                $settings = $data['settings'][$formatId] ?? [];

                $sync[$formatId] = [
                    'sku'        => $data['sku'][$formatId] ?? null,
                    'is_enabled' => true,
                    'settings'   => json_encode($settings),
                ];
            }

            $book->formats()->sync($sync);
            $book->update(['updated_by' => Auth::id()]);

            return response()->json(['status' => 'success', 'message' => 'Formats saved']);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    /* ───────────────────────── Tab 3: Files & DRM ───────────────────────── */

    public function storeFilesold(Request $request, Book $book)
    {
        $this->authorizeOwner($book);

        try {
            foreach ((array) $request->file('files', []) as $formatId => $group) {
                foreach (['epub', 'pdf', 'cover_preview', 'sample_audio'] as $type) {
                    if (! empty($group[$type])) {
                        $file = $group[$type];

                        $originalName = $file->getClientOriginalName();
                        $fileSize     = $file->getSize();
                        $path         = $this->storeUpload($file, 'books/files');

                        BookFile::updateOrCreate(
                            ['book_id' => $book->id, 'book_format_id' => $formatId, 'file_type' => $type],
                            [
                                'file_name' => $originalName,
                                'file_path' => $path,
                                'file_size' => $fileSize,
                                'status'    => 'uploaded',
                            ]
                        );
                    }
                }
            }

            foreach ($request->input('chapters', []) as $i => $chapter) {
                if (empty($chapter['title'])) {
                    continue;
                }

                $audioFile = $request->file("chapters.$i.audio_file");
                $audioSize = $audioFile ? $audioFile->getSize() : null;
                $audioPath = $audioFile ? $this->storeUpload($audioFile, 'books/chapters') : null;

                $updateData = [
                    'title'      => $chapter['title'],
                    'sort_order' => $i,
                ];

                // ✅ Only overwrite path/size/status if a NEW file was actually uploaded,
                // otherwise editing a chapter title would wipe out its existing audio.
                if ($audioFile) {
                    $updateData['audio_file_path'] = $audioPath;
                    $updateData['file_size']       = $audioSize;
                    $updateData['status']          = 'uploaded';
                }

                BookChapter::updateOrCreate(
                    ['book_id' => $book->id, 'chapter_number' => $i + 1],
                    $updateData
                );
            }

            $book->update(['updated_by' => Auth::id()]);

            return response()->json(['status' => 'success', 'message' => 'Files saved']);

        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function storeFiles(Request $request, Book $book)
{


    $this->authorizeOwner($book);

    $request->validate([
        'files'                  => 'nullable|array',
        'files.*.epub'           => 'nullable|file|mimes:epub',
        'files.*.pdf'            => 'nullable|file|mimes:pdf',
        'files.*.cover_preview'  => 'nullable|file|mimes:epub,pdf',
        'files.*.sample_audio'   => 'nullable|file|mimes:mp3,wav,m4a,aac',
    ]);

    try {
        // ── Pass 1: validate every uploaded EPUB BEFORE anything touches the DB ──
        $epubValidations = []; // formatId => validation result

        foreach ((array) $request->file('files', []) as $formatId => $group) {
            if (! empty($group['epub'])) {
                $validation = $this->validateEpubFile($group['epub']);
                $epubValidations[$formatId] = $validation;

                if (! $validation['valid']) {
                    return response()->json([
                        'status'      => 'error',
                        'message'     => 'EPUB validation failed: '
                            . ($validation['messages'][0]['message'] ?? 'Invalid EPUB file.'),
                        'epub_errors' => $validation['messages'],
                        'field'       => "files.$formatId.epub",
                    ], 422);
                }
            }
        }

        // ── Pass 2: everything valid — safe to store now ──
        foreach ((array) $request->file('files', []) as $formatId => $group) {
            foreach (['epub', 'pdf', 'cover_preview', 'sample_audio'] as $type) {
                if (! empty($group[$type])) {
                    $file = $group[$type];

                    $originalName = $file->getClientOriginalName();
                    $fileSize     = $file->getSize();
                    $path         = $this->storeUpload($file, 'books/files');

                    BookFile::updateOrCreate(
                        ['book_id' => $book->id, 'book_format_id' => $formatId, 'file_type' => $type],
                        [
                            'file_name' => $originalName,
                            'file_path' => $path,
                            'file_size' => $fileSize,
                            'status'    => 'uploaded',
                        ]
                    );
                }
            }
        }

        foreach ($request->input('chapters', []) as $i => $chapter) {
            if (empty($chapter['title'])) {
                continue;
            }

            $audioFile = $request->file("chapters.$i.audio_file");
            $audioSize = $audioFile ? $audioFile->getSize() : null;
            $audioPath = $audioFile ? $this->storeUpload($audioFile, 'books/chapters') : null;

            $updateData = ['title' => $chapter['title'], 'sort_order' => $i];

            if ($audioFile) {
                $updateData['audio_file_path'] = $audioPath;
                $updateData['file_size']       = $audioSize;
                $updateData['status']          = 'uploaded';
            }

            BookChapter::updateOrCreate(
                ['book_id' => $book->id, 'chapter_number' => $i + 1],
                $updateData
            );
        }

        $book->update(['updated_by' => Auth::id()]);

        return response()->json(['status' => 'success', 'message' => 'Files saved']);

    } catch (\Throwable $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
    }
}

/* ── Remove a single uploaded file (EPUB, PDF, cover preview, sample audio) ── */
public function destroyFile(BookFile $bookFile)
{
    $this->authorizeOwner($bookFile->book);

    $this->deleteUpload($bookFile->file_path);
    $bookFile->delete();

    return response()->json([
        'status'  => 'success',
        'message' => 'File removed successfully.',
    ]);
}

/* ── Remove a chapter's audio file only (keeps the chapter row/title) ── */
public function destroyChapterAudio(BookChapter $chapter)
{
    $this->authorizeOwner($chapter->book);

    if ($chapter->audio_file_path) {
        $this->deleteUpload($chapter->audio_file_path);
    }

    $chapter->update([
        'audio_file_path' => null,
        'file_size'        => null,
        'status'           => 'pending',
    ]);

    return response()->json([
        'status'  => 'success',
        'message' => 'Chapter audio removed.',
    ]);
}

private function deleteUpload(?string $relativePath): void
{
    if ($relativePath && File::exists(base_path($relativePath))) {
        File::delete(base_path($relativePath));
    }
}
/**
 * Runs the EPUB through scripts/validate-epub.mjs (epubcheck-ts + @smoores/epub)
 * and returns a normalized result. Never leaves a temp file behind.
 */
private function validateEpubFile(UploadedFile $file): array
{
    $tempPath = $file->store('temp/epub', 'local');
    $fullPath = Storage::disk('local')->path($tempPath);

    $nodeBinary = config('services.node.path', 'node');
    $scriptPath = base_path('scripts/validate-epub.mjs');

    $result = Process::timeout(120)->run([$nodeBinary, $scriptPath, $fullPath]);

    Storage::disk('local')->delete($tempPath);

    $report = json_decode(trim($result->output()), true);

    if (! is_array($report) || isset($report['error'])) {
        return [
            'valid'    => false,
            'messages' => [[
                'severity' => 'FATAL',
                'message'  => $report['error'] ?? 'EPUB validation script produced no readable output.',
            ]],
            'metadata' => null,
        ];
    }

    // Rule IDs we choose to treat as non-blocking (mirrors what most storefronts tolerate)
    $ignoredCodes = ['OPF-085', 'OPF-092', 'RSC-005'];

    $messages = $report['messages'] ?? [];

    $blockingErrors = array_filter($messages, function ($m) use ($ignoredCodes) {
        return strtoupper($m['severity'] ?? '') === 'ERROR'
            && ! in_array($m['id'] ?? '', $ignoredCodes, true);
    });

    return [
        'valid'        => empty($blockingErrors),
        'messages'     => $messages,
        'errorCount'   => $report['errorCount'] ?? null,
        'warningCount' => $report['warningCount'] ?? null,
        'metadata'     => $report['metadata'] ?? null,
    ];
}




    /* ───────────────────────── Tab 4: Pricing ───────────────────────── */

public function storePricing(Request $request, Book $book)
{
    $this->authorizeOwner($book);

    $data = $request->validate(['prices' => 'required|array']);

    try {
        foreach ($data['prices'] as $formatId => $countryPrices) {
            foreach ($countryPrices as $countryId => $priceData) {
                if (empty($priceData['price'])) {
                    continue;
                }

                $applyTax   = isset($priceData['apply_tax']);
                $isOnSale   = isset($priceData['is_on_sale']);

                $price      = round((float) $priceData['price'], 2, PHP_ROUND_HALF_UP);
                $salePrice  = ! empty($priceData['sale_price'])
                    ? round((float) $priceData['sale_price'], 2, PHP_ROUND_HALF_UP)
                    : null;
                $taxRate    = $applyTax ? (float) ($priceData['tax_rate'] ?? 0) : 0;

                $base = ($isOnSale && $salePrice !== null && $salePrice < $price)
                    ? $salePrice
                    : $price;

                // Exact final price (e.g. 100.70)
                $finalPrice = round($base + ($base * $taxRate / 100), 2, PHP_ROUND_HALF_UP);

                // Rounded to nearest whole number (e.g. 101)
                $finalPriceRounded = (int) round($finalPrice, 0, PHP_ROUND_HALF_UP);

                // ✅ NEW: the adjustment made by rounding (e.g. 101 - 100.70 = 0.30)
                $roundOffAmount = round($finalPriceRounded - $finalPrice, 2, PHP_ROUND_HALF_UP);

                BookPrice::updateOrCreate(
                    ['book_id' => $book->id, 'book_format_id' => $formatId, 'country_id' => $countryId],
                    [
                        'currency_id'          => $priceData['currency_id'] ?? null,
                        'tax_id'               => $applyTax ? ($priceData['tax_id'] ?? null) : null,
                        'tax_rate_snapshot'    => $applyTax ? ($priceData['tax_rate'] ?? null) : null,
                        'price'                => $price,
                        'sale_price'           => $salePrice,
                        'discount_percent'     => $priceData['discount_percent'] ?? null,
                        'is_on_sale'           => $isOnSale,
                        'is_active'            => true,
                        'final_price'          => $finalPrice,          // 100.70
                        'final_price_rounded'  => $finalPriceRounded,   // 101
                        'round_off_amount'     => $roundOffAmount,      // +0.30
                    ]
                );
            }
        }

        $book->update(['updated_by' => Auth::id()]);

        return response()->json(['status' => 'success', 'message' => 'Pricing saved']);
    } catch (\Throwable $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
    }
}

    /* ───────────────────────── Tab 5: Inventory ───────────────────────── */

    public function storeInventory(Request $request, Book $book)
    {
        $this->authorizeOwner($book);

        try {
            foreach ($request->input('inventory', []) as $formatId => $inv) {
                BookInventory::updateOrCreate(
                    ['book_id' => $book->id, 'book_format_id' => $formatId],
                    [
                        'manage_stock'        => isset($inv['manage_stock']),
                        'sku'                  => $inv['sku'] ?? null,
                        'barcode'              => $inv['barcode'] ?? null,
                        'stock_quantity'       => $inv['stock_quantity'] ?? 0,
                        'low_stock_threshold'  => $inv['low_stock_threshold'] ?? null,
                        'stock_status'         => $inv['stock_status'] ?? 'in_stock',
                        'allow_backorders'     => isset($inv['allow_backorders']),
                        'sold_individually'    => isset($inv['sold_individually']),
                        'stock_visibility'     => isset($inv['stock_visibility']),
                    ]
                );
            }

            $book->update(['updated_by' => Auth::id()]);

            return response()->json(['status' => 'success', 'message' => 'Inventory saved']);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    /* ───────────────────────── Tab 6: Shipping ───────────────────────── */

    public function storeShipping(Request $request, Book $book)
    {
        $this->authorizeOwner($book);

        try {
            $shippingInput = $request->input('shipping', []);

            $shipping = BookShipping::updateOrCreate(
                ['book_id' => $book->id],
                [
                    'enable_shipping'      => isset($shippingInput['enable_shipping']),
                    'shipping_class'       => $shippingInput['shipping_class'] ?? null,
                    'shipping_profile'     => $shippingInput['shipping_profile'] ?? null,
                    'processing_time_min'  => $shippingInput['processing_time_min'] ?? null,
                    'processing_time_max'  => $shippingInput['processing_time_max'] ?? null,
                    'weight'               => $shippingInput['weight'] ?? null,
                    'length'               => $shippingInput['length'] ?? null,
                    'width'                => $shippingInput['width'] ?? null,
                    'height'               => $shippingInput['height'] ?? null,
                    'ships_from'           => $shippingInput['ships_from'] ?? null,
                    'ships_to'             => $shippingInput['ships_to'] ?? null,
                    'shipping_zone'        => $shippingInput['shipping_zone'] ?? null,
                ]
            );

            $shipping->methods()->delete();
            foreach ($request->input('shipping_methods', []) as $i => $method) {
                if (empty($method['name'])) {
                    continue;
                }
                BookShippingMethod::create([
                    'book_shipping_id' => $shipping->id,
                    'name'             => $method['name'],
                    'cost'             => $method['cost'] ?? 0,
                    'is_active'        => isset($method['is_active']),
                    'sort_order'       => $i,
                ]);
            }

            $book->update(['updated_by' => Auth::id()]);

            return response()->json(['status' => 'success', 'message' => 'Shipping saved']);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    /* ───────────────────────── Tab 7: SEO & Meta ───────────────────────── */

    public function storeSeo(Request $request, Book $book)
    {
        $this->authorizeOwner($book);

        try {
            $seoInput = $request->input('seo', []);

            // Meta keywords arrive as a JSON string from the frontend
            // (JSON.stringify in _scripts.blade.php). Decode it into a
            // real array here so Eloquent's 'array' cast doesn't encode it twice.
            $metaKeywords = [];
            if (isset($seoInput['meta_keywords'])) {
                $metaKeywords = is_array($seoInput['meta_keywords'])
                    ? $seoInput['meta_keywords']
                    : (json_decode($seoInput['meta_keywords'], true) ?? []);
            }

            BookSeo::updateOrCreate(
                ['book_id' => $book->id],
                [
                    'seo_title'               => $seoInput['seo_title'] ?? null,
                    'meta_description'        => $seoInput['meta_description'] ?? null,
                    'url_slug'                => $seoInput['url_slug'] ?? $book->slug,
                    'meta_keywords'           => $metaKeywords,
                    'canonical_url'           => $seoInput['canonical_url'] ?? null,
                    'allow_index'             => isset($seoInput['allow_index']),
                    'allow_follow'            => isset($seoInput['allow_follow']),
                    'enable_structured_data'  => isset($seoInput['enable_structured_data']),
                    'robots_setting'          => $seoInput['robots_setting'] ?? 'index_follow',
                ]
            );

            $book->update(['updated_by' => Auth::id()]);

            return response()->json(['status' => 'success', 'message' => 'SEO saved']);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    /* ───────────────────────── Tab 8: Publishing (final) ───────────────────────── */

public function publish(Request $request, Book $book)
{
    $this->authorizeOwner($book);

    try {
        $book->update([
            'status'                    => $request->input('status', 'draft'),
            'visibility'                => $request->input('visibility', 'public'),
            'show_in_store'             => $request->boolean('show_in_store'),
            'is_featured'               => $request->boolean('is_featured'),
            'is_bestseller'             => $request->boolean('is_bestseller'),
            'is_never_miss_to_read'     => $request->boolean('is_never_miss_to_read'),
            'badges'                    => $request->input('badges', []),
            'publish_type'              => $request->input('publish_type', 'immediately'),
            'publication_date'          => $request->input('publication_date'),
            'publication_time'          => $request->input('publication_time'),
            'allow_pre_order'           => $request->boolean('allow_pre_order'),
            'pre_order_start_date'      => $request->input('pre_order_start_date'),
            'pre_order_end_date'        => $request->input('pre_order_end_date'),
            'allow_reviews'             => $request->boolean('allow_reviews'),
            'enable_wishlist'           => $request->boolean('enable_wishlist'),
            'enable_share'              => $request->boolean('enable_share'),
            'enable_compare'            => $request->boolean('enable_compare'),
            'send_email_notification'   => $request->boolean('send_email_notification'),
            'updated_by'                => Auth::id(),
        ]);

        return response()->json([
            'status'   => 'success',
            'message'  => "Book '{$book->title}' saved successfully! 🎉",
            'redirect' => route('books.index'),
        ]);
    } catch (\Throwable $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
    }
}

    /* ───────────────────────── Helpers ───────────────────────── */

    private function storeUpload($file, string $folder): string
    {
        $uploadPath = base_path("uploads/{$folder}"); // ✅ now root path, same as AuthorController

        if (! File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true);
        }

        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        $file->move($uploadPath, $filename);

        return "uploads/{$folder}/{$filename}"; // stored value stays the same format
    }

    /* ── Media: Trailer + Gallery ── */
    public function storeMedia(Request $request, Book $book)
    {
        $this->authorizeOwner($book);

        $request->validate([
            'trailer_type'      => 'nullable|in:upload,youtube,vimeo',
            'trailer_file'      => 'nullable|file|mimes:mp4,mov,webm|max:102400', // 100MB
            'trailer_url'       => 'nullable|url',
            'gallery_images'    => 'nullable|array',
            'gallery_images.*'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120', // 5MB each
        ]);

        try {
            // ── Trailer ──
            if ($request->trailer_type === 'upload' && $request->hasFile('trailer_file')) {
                $book->trailer_video = $this->storeUpload($request->file('trailer_file'), 'books/trailers');
                $book->trailer_type  = 'upload';
            } elseif (in_array($request->trailer_type, ['youtube', 'vimeo']) && $request->filled('trailer_url')) {
                $book->trailer_video = $request->trailer_url;
                $book->trailer_type  = $request->trailer_type;
            } elseif ($request->trailer_type === '' || $request->trailer_type === null) {
                // "No trailer" selected — leave existing value untouched unless explicitly cleared
            }
            $book->updated_by = Auth::id();
            $book->save();

            // ── Gallery images (append, don't wipe existing) ──
            if ($request->hasFile('gallery_images')) {
                $startOrder = (int) ($book->galleryImages()->max('sort_order') ?? 0) + 1;

                foreach ($request->file('gallery_images') as $i => $file) {
                    BookGalleryImage::create([
                        'book_id'    => $book->id,
                        'image_path' => $this->storeUpload($file, 'books/gallery'),
                        'sort_order' => $startOrder + $i,
                    ]);
                }
            }

            $book->refresh()->load('galleryImages');

            return response()->json([
                'status'       => 'success',
                'message'      => 'Media saved',
                'book_id'      => $book->id,
                'gallery'      => $book->galleryImages,
                'trailer_url'  => $book->trailer_video_url,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function deleteGalleryImage(BookGalleryImage $image)
    {
        $this->authorizeOwner($image->book);

        try {
            $path = public_path($image->image_path);
            if (File::exists($path)) {
                File::delete($path);
            }
            $image->delete();

            return response()->json(['status' => 'success', 'message' => 'Image removed']);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    /* ── Related Books ── */
    public function storeRelated(Request $request, Book $book)
    {
        $this->authorizeOwner($book);

        $data = $request->validate([
            'related_books'   => 'nullable|array',
            'related_books.*' => 'exists:books,id|different:book_id',
        ]);

        try {
            $sync = [];
            foreach ($data['related_books'] ?? [] as $i => $id) {
                $sync[$id] = ['sort_order' => $i];
            }
            $book->relatedBooks()->sync($sync);
            $book->update(['updated_by' => Auth::id()]);

            return response()->json(['status' => 'success', 'message' => 'Related books saved']);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    /* For select2 AJAX search when picking related books — scoped so you can't
       link to a book you don't own (admin can search everything). */
    public function searchForSelect(Request $request)
    {
        $term = $request->get('q', '');
        $excludeId = $request->get('exclude');

        $books = $this->scopeToOwner(Book::query())
            ->where('title', 'like', "%{$term}%")
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->limit(20)
            ->get(['id', 'title', 'cover_image']);

        return response()->json($books->map(fn ($b) => ['id' => $b->id, 'text' => $b->title]));
    }

public function checkIsbn(string $isbn)
{
    $clean = preg_replace('/[-\s]/', '', $isbn);
 
    // Basic length check first — no need to hit network or cache for this
    if (! in_array(strlen($clean), [10, 13])) {
        return response()->json([
            'status'  => 'invalid',
            'isbn'    => $clean,
            'message' => "ISBN {$clean} must be 10 or 13 digits.",
        ]);
    }
 
    $checksumValid = $this->isChecksumValidIsbn($clean);
 
    // Cache per-ISBN for 6 hours. This means:
    // - repeated blur events on the same value do NOT re-hit the network
    // - if OpenLibrary/Google are rate-limiting you, a cached "valid" or
    //   "invalid" result still comes back instantly instead of failing
    $result = Cache::remember("isbn_check_{$clean}", now()->addHours(6), function () use ($clean) {
        $r = $this->checkIsbnOnOpenLibrary($clean);
        if ($r['status'] !== 'unknown') {
            return $r;
        }
 
        $r = $this->checkIsbnOnGoogleBooks($clean);
        if ($r['status'] !== 'unknown') {
            return $r;
        }
 
        // 3rd fallback source
        return $this->checkIsbnOnOpenLibraryBibkeys($clean);
    });
 
    if ($result['status'] === 'valid') {
        return response()->json([
            'status'  => 'valid',
            'isbn'    => $clean,
            'title'   => $result['title'] ?? null,
            'source'  => $result['source'] ?? null,
            'message' => $result['title']
                ? "Valid ISBN {$clean} — matched \"{$result['title']}\""
                : "ISBN {$clean} is valid.",
        ]);
    }
 
    if ($result['status'] === 'invalid') {
        return response()->json([
            'status'  => $checksumValid ? 'checksum_only' : 'invalid',
            'isbn'    => $clean,
            'message' => $checksumValid
                ? "ISBN {$clean} format is valid but wasn't found in external catalogs."
                : "ISBN {$clean} was not found. Please double-check it.",
        ]);
    }
 
    // All three sources were unreachable/unknown
    return response()->json([
        'status'  => $checksumValid ? 'checksum_only' : 'invalid',
        'isbn'    => $clean,
        'message' => $checksumValid
            ? "ISBN {$clean} format is valid. Could not confirm against external catalogs right now, but you can continue."
            : "ISBN {$clean} failed checksum validation and could not be confirmed.",
    ]);
}
 
/**
 * ISBN-10 and ISBN-13 checksum validation. Pure math, no network — this is
 * why the ISBN field can now give an immediate, reliable answer even if
 * OpenLibrary/Google Books are both down or rate-limited.
 * (Unchanged from your existing code.)
 */
private function isChecksumValidIsbn(string $isbn): bool
{
    if (preg_match('/^\d{9}[\dX]$/i', $isbn)) {
        // ISBN-10
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $isbn[$i] * (10 - $i);
        }
        $last = strtoupper($isbn[9]) === 'X' ? 10 : (int) $isbn[9];
        $sum += $last;
        return $sum % 11 === 0;
    }
 
    if (preg_match('/^\d{13}$/', $isbn)) {
        // ISBN-13
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += (int) $isbn[$i] * ($i % 2 === 0 ? 1 : 3);
        }
        return $sum % 10 === 0;
    }
 
    return false;
}
 
/**
 * Source 1: OpenLibrary "volumes/brief" endpoint.
 * (Unchanged from your existing code.)
 */
private function checkIsbnOnOpenLibrary(string $clean): array
{
    try {
        $response = Http::timeout(8)->retry(2, 300)
            ->get("https://openlibrary.org/api/volumes/brief/isbn/{$clean}.json");
 
        if (! $response->successful()) {
            return ['status' => 'unknown'];
        }
 
        $data = $response->json();
        $hasRecord = ! empty($data['records']) && is_array($data['records']);
 
        if ($hasRecord) {
            $record = array_values($data['records'])[0];
            $title  = $record['data']['title'] ?? ($record['details']['title'] ?? null);
            return ['status' => 'valid', 'title' => $title, 'source' => 'OpenLibrary'];
        }
 
        return ['status' => 'invalid'];
    } catch (\Throwable $e) {
        return ['status' => 'unknown'];
    }
}
 
/**
 * Source 2: Google Books API.
 * (Unchanged from your existing code.)
 */
private function checkIsbnOnGoogleBooks(string $clean): array
{
    try {
        $response = Http::timeout(8)->retry(2, 300)
            ->get('https://www.googleapis.com/books/v1/volumes', ['q' => "isbn:{$clean}"]);
 
        if (! $response->successful()) {
            return ['status' => 'unknown'];
        }
 
        $data = $response->json();
 
        if (! empty($data['items']) && is_array($data['items'])) {
            $title = $data['items'][0]['volumeInfo']['title'] ?? null;
            return ['status' => 'valid', 'title' => $title, 'source' => 'Google Books'];
        }
 
        return ['status' => 'invalid'];
    } catch (\Throwable $e) {
        return ['status' => 'unknown'];
    }
}
 
/**
 * Source 3 (NEW): OpenLibrary "bibkeys/data" endpoint. Different endpoint
 * and code path from checkIsbnOnOpenLibrary() above, so it frequently
 * succeeds even when that one is rate-limited or briefly down.
 */
private function checkIsbnOnOpenLibraryBibkeys(string $clean): array
{
    try {
        $response = Http::timeout(8)->retry(2, 300)->get('https://openlibrary.org/api/books', [
            'bibkeys' => "ISBN:{$clean}",
            'format'  => 'json',
            'jscmd'   => 'data',
        ]);
 
        if (! $response->successful()) {
            return ['status' => 'unknown'];
        }
 
        $data = $response->json();
        $key  = "ISBN:{$clean}";
 
        if (! empty($data[$key])) {
            return [
                'status' => 'valid',
                'title'  => $data[$key]['title'] ?? null,
                'source' => 'OpenLibrary',
            ];
        }
 
        return ['status' => 'invalid'];
    } catch (\Throwable $e) {
        return ['status' => 'unknown'];
    }
}



}
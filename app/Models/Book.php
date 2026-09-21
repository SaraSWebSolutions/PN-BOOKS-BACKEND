<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Book extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id', 'subcategory_id', 'series_id', 'author_id', 'publisher_id',
        'title', 'slug', 'subtitle', 'isbn',
        // 'language' removed — a book can now have MULTIPLE languages via the
        // book_languages pivot table (see languages() relation below).
        'short_description', 'description', 'cover_image',

        'status', 'visibility', 'show_in_store', 'is_featured', 'is_bestseller', 'badges',
        'publish_type', 'publication_date', 'publication_time',
        'allow_pre_order', 'pre_order_start_date', 'pre_order_end_date',
        'allow_reviews', 'enable_wishlist', 'enable_share', 'enable_compare', 'send_email_notification',

        'created_by', 'updated_by','is_never_miss_to_read'
    ];

    protected $casts = [
        'badges'                   => 'array',
        'show_in_store'            => 'boolean',
        'is_featured'              => 'boolean',
        'is_bestseller'            => 'boolean',
        'allow_pre_order'          => 'boolean',
        'allow_reviews'            => 'boolean',
        'enable_wishlist'          => 'boolean',
        'enable_share'             => 'boolean',
        'is_never_miss_to_read'     => 'boolean',
        'enable_compare'           => 'boolean',
        'send_email_notification'  => 'boolean',
        'publication_date'         => 'date',
        'pre_order_start_date'     => 'date',
        'pre_order_end_date'       => 'date',
    ];

protected $appends = ['cover_image_url', 'trailer_video_url', 'average_rating', 'reviews_count'];

    /* ───────────────────────── Boot ───────────────────────── */

    protected static function booted()
    {
        static::creating(function (Book $book) {
            if (empty($book->slug)) {
                $book->slug = static::uniqueSlug($book->title);
            }
        });
    }

    public static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 1;
        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        return $slug;
    }

    public function scopeNeverMissToRead($query)
{
    return $query->where('is_never_miss_to_read', true);
}
    /* ───────────────────────── Relationships ───────────────────────── */

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function series()
    {
        return $this->belongsTo(Series::class);
    }

    public function author()
    {
        return $this->belongsTo(AuthorProfile::class, 'author_id');
    }
    public function wishlistedBy()
{
    return $this->belongsToMany(User::class, 'wishlists')->withTimestamps();
}

    public function publisher()
    {
        return $this->belongsTo(PublisherProfile::class, 'publisher_id');
    }

    // A book can be available in several languages (e.g. English + Tamil editions)
    public function languages()
    {
        return $this->belongsToMany(Language::class, 'book_languages')->withTimestamps();
    }

    // Formats enabled for this book, with pivot sku/is_enabled/settings
    public function formats()
    {
        return $this->belongsToMany(BookFormat::class, 'book_book_format')
            ->withPivot(['id', 'is_enabled', 'sku', 'settings'])
            ->withTimestamps();
    }

    public function prices()
    {
        return $this->hasMany(BookPrice::class);
    }

    public function files()
    {
        return $this->hasMany(BookFile::class);
    }

    public function chapters()
    {
        return $this->hasMany(BookChapter::class)->orderBy('sort_order');
    }

    public function inventory()
    {
        return $this->hasMany(BookInventory::class);
    }

    public function shipping()
    {
        return $this->hasOne(BookShipping::class);
    }

    public function seo()
    {
        return $this->hasOne(BookSeo::class);
    }

    // app/Models/Book.php

public function getEbookFileUrlAttribute(): ?string
{
    $file = $this->files->firstWhere('file_type', 'epub')
        ?? $this->files->firstWhere('file_type', 'pdf');

    return $file ? asset($file->file_path) : null;
}

public function getAudiobookSampleUrlAttribute(): ?string
{
    $file = $this->files->firstWhere('file_type', 'sample_audio');

    return $file ? asset($file->file_path) : null;
}

    /* ───────────────────────── Accessors ───────────────────────── */

    public function getCoverImageUrlAttribute(): ?string
    {
        return $this->cover_image ? asset($this->cover_image) : null;
    }

    /* ───────────────────────── Scopes ───────────────────────── */

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function galleryImages()
{
    return $this->hasMany(BookGalleryImage::class)->orderBy('sort_order');
}

public function reviews()
{
    return $this->hasMany(BookReview::class);
}

public function approvedReviews()
{
    return $this->hasMany(BookReview::class)->approved();
}

// Related books this book points to
public function relatedBooks()
{
    return $this->belongsToMany(
        Book::class, 'book_related', 'book_id', 'related_book_id'
    )->withPivot('sort_order')->orderBy('book_related.sort_order');
}

/* Rating accessors */
public function getAverageRatingAttribute(): float
{
    return round($this->approvedReviews()->avg('rating') ?? 0, 1);
}

public function getReviewsCountAttribute(): int
{
    return $this->approvedReviews()->count();
}



public function getTrailerVideoUrlAttribute(): ?string
{
    if (!$this->trailer_video) return null;
    return $this->trailer_type === 'upload' ? asset($this->trailer_video) : $this->trailer_video;
}
}
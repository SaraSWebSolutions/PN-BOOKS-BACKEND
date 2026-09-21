<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'description', 'target_type',
        'book_id', 'category_id', 'book_format_id', 'country_id',
        'discount_type', 'discount_value',
        'starts_at', 'ends_at', 'status',
    ];

    protected $casts = [
        'status'         => 'boolean',
        'discount_value' => 'decimal:2',
        'starts_at'      => 'datetime',
        'ends_at'        => 'datetime',
    ];

    /* ───────────── Relationships ───────────── */

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function format()
    {
        return $this->belongsTo(BookFormat::class, 'book_format_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class); // null = all countries
    }

    /* ───────────── Scopes ───────────── */

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    // Only offers that are currently within their date window (or have no dates set)
 public function scopeRunningNow($query)
{
    $today = now()->toDateString(); // e.g. "2026-09-16" — date only, time ignored

    return $query->where(function ($q) use ($today) {
            $q->whereNull('starts_at')->orWhereDate('starts_at', '<=', $today);
        })
        ->where(function ($q) use ($today) {
            $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', $today);
        });
}

    // Offers visible to a given country: either country-specific or global (country_id null)
    public function scopeForCountry($query, ?int $countryId)
    {
        return $query->where(function ($q) use ($countryId) {
            $q->whereNull('country_id');
            if ($countryId) {
                $q->orWhere('country_id', $countryId);
            }
        });
    }

    /* ───────────── Accessors ───────────── */

  public function getIsRunningAttribute(): bool
{
    $today = now()->toDateString();
    $afterStart = ! $this->starts_at || $this->starts_at->toDateString() <= $today;
    $beforeEnd  = ! $this->ends_at || $this->ends_at->toDateString() >= $today;
    return $this->status && $afterStart && $beforeEnd;
}

    public function getTargetLabelAttribute(): string
    {
        return match ($this->target_type) {
            'book'     => $this->book->title ?? 'Unknown book',
            'category' => $this->category->name ?? 'Unknown category',
            'format'   => $this->format->name ?? 'Unknown format',
            default    => 'All Books',
        };
    }
}
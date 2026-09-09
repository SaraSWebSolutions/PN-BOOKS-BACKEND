<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name_en', 'name_ms', 'slug', 'description_en', 'description_ms',
        'image', 'is_active', 'created_by', 'updated_by',
    ];
    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ── Boot: auto slug ──────────────────────────────────────
   protected static function booted()
    {
        static::saving(function (Category $category) {
            if (empty($category->slug) || $category->isDirty('name_en')) {
                $category->slug = Str::slug($category->name_en);
            }
        });
    }

        public function getNameAttribute(): string
    {
        $locale = app()->getLocale(); // 'en' or 'ms'
        return $this->{"name_{$locale}"} ?: $this->name_en;
    }

    public function getDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->{"description_{$locale}"} ?: $this->description_en;
    }

    // ...relationships and scopes unchanged, but scopeOrdered should sort by name_en
    public function scopeOrdered($query)
    {
        return $query->orderBy('name_en');
    }

    // ── Relationships ────────────────────────────────────────
    public function subcategories()
    {
        return $this->hasMany(Subcategory::class);
    }

    public function books()
    {
        return $this->hasMany(Book::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ── Scopes ────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

 
}

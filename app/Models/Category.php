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

    // ensures image_url is always in JSON output (admin AJAX + API), no extra code needed elsewhere
    protected $appends = ['image_url'];

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
        $locale = app()->getLocale();
        return $this->{"name_{$locale}"} ?: $this->name_en;
    }

    public function getDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->{"description_{$locale}"} ?: $this->description_en;
    }

    // builds a hittable URL for the image, since it's stored outside public/
    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        return route('categories.image', ['filename' => basename($this->image)]);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('name_en');
    }

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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
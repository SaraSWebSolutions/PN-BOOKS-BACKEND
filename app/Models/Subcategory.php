<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Subcategory extends Model
{
    use SoftDeletes;

      protected $fillable = [
        'category_id', 'name_en', 'name_ms', 'slug',
        'description_en', 'description_ms', 'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = ['is_active' => 'boolean'];

    protected static function booted()
    {
        static::saving(function (Subcategory $sub) {
            if (empty($sub->slug) || $sub->isDirty('name_en')) {
                $sub->slug = Str::slug($sub->name_en);
            }
        });
    }

   public function getNameAttribute(): ?string
{
    $locale = app()->getLocale();
    return $this->{"name_{$locale}"} ?: ($this->name_en ?: null);
}
    public function getDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->{"description_{$locale}"} ?: $this->description_en;
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('name_en');
    }

    // ── Relationships ────────────────────────────────────────
    public function category()
    {
        return $this->belongsTo(Category::class);
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

  

    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }
}

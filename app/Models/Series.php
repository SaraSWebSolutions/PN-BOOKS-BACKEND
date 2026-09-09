<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Series extends Model
{
    use SoftDeletes;

    protected $table = 'series';

    protected $fillable = [
        'name', 'slug', 'description', 'cover_image',
        'publisher_id', 'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function (Series $series) {
            if (empty($series->slug) || $series->isDirty('name')) {
                $series->slug = Str::slug($series->name);
            }
        });
    }

    // ── Relationships ────────────────────────────────────────
    public function publisher()
    {
        return $this->belongsTo(User::class, 'publisher_id');
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

    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }
}

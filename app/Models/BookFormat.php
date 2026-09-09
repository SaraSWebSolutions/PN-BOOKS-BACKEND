<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookFormat extends Model
{
    protected $fillable = [
        'code',
        'name',
        'icon',
        'requires_shipping',
        'status',
    ];

    protected $casts = [
        'status'             => 'boolean',
        'requires_shipping'  => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }

    public function books()
    {
        return $this->belongsToMany(Book::class, 'book_book_format')
            ->withPivot(['id', 'is_enabled', 'sku', 'settings'])
            ->withTimestamps();
    }
}
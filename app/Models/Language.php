<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $fillable = [
        'name',
        'native_name',
        'code',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('name', 'asc');
    }

    // A language can belong to many books, and a book can have many languages.
    // Requires the book_languages pivot table + a Book model.
    public function books()
    {
        return $this->belongsToMany(Book::class, 'book_languages');
    }
}
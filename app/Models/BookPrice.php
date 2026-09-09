<?php
// app/Models/BookPrice.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookPrice extends Model
{
    protected $fillable = [
        'book_id', 'book_format_id', 'country_id', 'currency_id',
        'price', 'sale_price', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price'     => 'decimal:2',
        'sale_price' => 'decimal:2',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function format()
    {
        return $this->belongsTo(BookFormat::class, 'book_format_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookShipping extends Model
{
     protected $table = 'book_shipping';
    protected $fillable = [
        'book_id', 'enable_shipping', 'shipping_class', 'shipping_profile',
        'processing_time_min', 'processing_time_max',
        'weight', 'length', 'width', 'height',
        'ships_from', 'ships_to', 'shipping_zone',
    ];

    protected $casts = [
        'enable_shipping' => 'boolean',
        'weight'          => 'decimal:2',
        'length'          => 'decimal:2',
        'width'           => 'decimal:2',
        'height'          => 'decimal:2',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function methods()
    {
        return $this->hasMany(BookShippingMethod::class)->orderBy('sort_order');
    }
}
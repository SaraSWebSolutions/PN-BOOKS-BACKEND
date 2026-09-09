<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookShippingMethod extends Model
{

protected $table = 'book_shipping_methods';
    protected $fillable = ['book_shipping_id', 'name', 'cost', 'is_active', 'sort_order'];

    protected $casts = [
        'cost'      => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function shipping()
    {
        return $this->belongsTo(BookShipping::class, 'book_shipping_id');
    }
}
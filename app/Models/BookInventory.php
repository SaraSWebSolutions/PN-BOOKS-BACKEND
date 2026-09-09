<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookInventory extends Model
{
    protected $table = 'book_inventory';

    protected $fillable = [
        'book_id', 'book_format_id', 'manage_stock', 'sku', 'barcode',
        'stock_quantity', 'low_stock_threshold', 'stock_status',
        'allow_backorders', 'sold_individually', 'stock_visibility',
    ];

    protected $casts = [
        'manage_stock'      => 'boolean',
        'allow_backorders'  => 'boolean',
        'sold_individually' => 'boolean',
        'stock_visibility'  => 'boolean',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function format()
    {
        return $this->belongsTo(BookFormat::class, 'book_format_id');
    }
}
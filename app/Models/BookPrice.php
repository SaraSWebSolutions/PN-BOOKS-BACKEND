<?php
// app/Models/BookPrice.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookPrice extends Model
{
    protected $fillable = [
        'book_id', 'book_format_id', 'country_id', 'currency_id',
        'tax_id', 'tax_rate_snapshot',
        'price', 'sale_price', 'is_on_sale', 'is_active','discount_percent','final_price','final_price_rounded','round_off_amount',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'is_on_sale'         => 'boolean',
        'price'              => 'decimal:2',
        'sale_price'         => 'decimal:2',
        'tax_rate_snapshot'  => 'decimal:3',
        'discount_percent'   => 'decimal:2',
        'final_price'        => 'decimal:2',
        'final_price_rounded'  => 'integer',
        'round_off_amount'     => 'decimal:2',
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

    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }

    /** True only when a valid sale price is set AND the admin has the discount switched on. */
    public function getHasActiveDiscountAttribute(): bool
    {
        return $this->is_on_sale
            && ! is_null($this->sale_price)
            && (float) $this->sale_price < (float) $this->price;
    }

    /** The price used for calculations: sale price if the discount is active, otherwise regular price. */
    public function getEffectivePriceAttribute(): float
    {
        return $this->has_active_discount ? (float) $this->sale_price : (float) $this->price;
    }

    /** Discount %, rounded — 0 if no active discount. This is what powers the "38% OFF" badge. */
    public function getDiscountPercentAttribute(): int
    {
        if (! $this->has_active_discount || (float) $this->price <= 0) {
            return 0;
        }

        return (int) round((($this->price - $this->sale_price) / $this->price) * 100);
    }

    /** Final price the customer pays: effective price + tax, using the frozen rate. */
    public function getPriceInclTaxAttribute(): float
    {
        $base = $this->effective_price;
        $rate = (float) ($this->tax_rate_snapshot ?? 0);

        return round($base + ($base * $rate / 100), 2);
    }
}
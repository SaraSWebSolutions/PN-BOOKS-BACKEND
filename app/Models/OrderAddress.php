<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'type', 'full_name', 'phone', 'address_line1',
        'address_line2', 'city', 'state', 'postal_code', 'country_id',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
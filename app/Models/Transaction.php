<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'gateway', 'gateway_intent_id', 'gateway_payment_id',
        'amount', 'currency', 'status', 'checkout_url',
        'raw_request', 'raw_response', 'callback_payload', 'failure_reason',
    ];

    protected $casts = [
        'raw_request'      => 'array',
        'raw_response'     => 'array',
        'callback_payload' => 'array',
        'amount'           => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
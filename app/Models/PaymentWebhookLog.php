<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentWebhookLog extends Model
{
    protected $fillable = ['gateway', 'event_type', 'payload', 'signature_valid', 'processed'];

    protected $casts = [
        'payload'         => 'array',
        'signature_valid' => 'boolean',
        'processed'       => 'boolean',
    ];
}
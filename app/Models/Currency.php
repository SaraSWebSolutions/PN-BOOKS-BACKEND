<?php
// app/Models/Currency.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $table = 'currencies';

    protected $fillable = [
        'country_id',
        'code',
        'name',
        'symbol',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /* ===================== Relationships ===================== */

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /* ===================== Scopes ===================== */

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('name', 'asc');
    }
}
<?php
// app/Models/Country.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Country extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'countries';

    protected $fillable = [
        'name',
        'code',
        'phone_code',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /* ===================== Relationships ===================== */

    public function currencies()
    {
        return $this->hasMany(Currency::class);
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
    public function taxes()
{
    return $this->hasMany(Tax::class);
}

public function currency()
{
    return $this->hasOne(\App\Models\Currency::class);
}
}
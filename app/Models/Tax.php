<?php
// app/Models/Tax.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tax extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'taxes';

    protected $fillable = [
        'country_id',
        'tax_name',
        'tax_code',
        'tax_type',
        'tax_rate',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status'   => 'boolean',
        'tax_rate' => 'decimal:3',
    ];

    /* ===================== Relationships ===================== */

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /* ===================== Scopes ===================== */

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('tax_name', 'asc');
    }
}
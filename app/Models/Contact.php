<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    protected $fillable = [
        'name', 'code', 'type', 'email', 'phone', 'whatsapp',
        'date_of_birth', 'gender', 'address', 'city', 'state',
        'country', 'pincode', 'gstin', 'credit_limit',
        'opening_balance', 'avatar', 'notes', 'status',
        'business_location_id', 'created_by','pan_number','aadhaar_number'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'credit_limit'  => 'decimal:2',
        'opening_balance' => 'decimal:2',
    ];

    // ─── Type constants ───────────────────────────────────────────────
    const TYPE_CUSTOMER = 'customer';
    const TYPE_LEAD     = 'lead';
    const TYPE_BOTH     = 'both';
    const TYPE_SUPPLIER = 'supplier';

    public static function types(): array
    {
        return [
            self::TYPE_CUSTOMER => 'Customer',
            self::TYPE_LEAD     => 'Lead',
            self::TYPE_BOTH     => 'Both',
            self::TYPE_SUPPLIER => 'Supplier',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────
    public function businessLocation(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Scopes ───────────────────────────────────────────────────────
    public function scopeCustomers($query)
    {
        return $query->whereIn('type', [self::TYPE_CUSTOMER, self::TYPE_BOTH]);
    }

    public function scopeSuppliers($query)
    {
        return $query->where('type', self::TYPE_SUPPLIER);
    }

    public function scopeLeads($query)
    {
        return $query->where('type', self::TYPE_LEAD);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // ─── Helpers ─────────────────────────────────────────────────────
    public function getTypeLabel(): string
    {
        return self::types()[$this->type] ?? ucfirst($this->type);
    }

    public function isSupplier(): bool
    {
        return $this->type === self::TYPE_SUPPLIER;
    }

    public function isCustomer(): bool
    {
        return in_array($this->type, [self::TYPE_CUSTOMER, self::TYPE_BOTH]);
    }

    public static function generateCode(): string
    {
        $last = static::latest('id')->first();
        $next = $last ? ((int) ltrim($last->code, 'CON-') + 1) : 1;
        return 'CON-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
    public function salesTransactions()
{
    return $this->hasMany(\App\Models\SalesTransaction::class, 'contact_id');
}

public function bookings()
{
    return $this->hasMany(\App\Models\Booking::class, 'contact_id');
}


}
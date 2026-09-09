<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublisherProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'slug',
        'logo',
        'gst_number',
        'pan_number',
        'contact_person',
        'contact_phone',
        'contact_email',
        'company_address',
        'bank_account_name',
        'bank_account_number',
        'bank_ifsc',
        'verification_status',
        'status',
    ];
    
    protected $casts = [
    'status' => 'boolean',   // ✅ add this
];

    protected $appends = ['logo_url'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset($this->logo) : null;
    }
        public function scopeActive($query)
{
    return $query->where('status', true);
}
public function books()
{
    return $this->hasMany(Book::class, 'publisher_id');
}

}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthorProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pen_name',
        'slug',
        'bio',
        'website',
        'social_links',
        'profile_photo',
        'verification_status',
        'status',
        'commission_rate',
        'bank_account_name',
        'bank_account_number',
        'bank_ifsc',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
         'status'          => 'boolean',
    ];

    protected $appends = ['profile_photo_url'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->profile_photo ? asset($this->profile_photo) : null;
    }
    public function scopeActive($query)
{
    return $query->where('status', true);
}

public function books()
{
    return $this->hasMany(Book::class, 'author_id');
}



}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date_of_birth',
        'alternate_phone',
        'profile_photo',
        'gender',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',        // legacy free-text, kept as-is
        'country_id',      // NEW — FK to countries
        'language_id',     // NEW — FK to languages
        'newsletter_subscribed',
        'loyalty_points',
    ];

    protected $casts = [
        'date_of_birth'         => 'date',
        'newsletter_subscribed' => 'boolean',
        'loyalty_points'        => 'integer',
    ];

    protected $appends = ['profile_photo_url'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // NEW
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    // NEW
    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->profile_photo ? asset('storage/' . $this->profile_photo) : null;
    }
}
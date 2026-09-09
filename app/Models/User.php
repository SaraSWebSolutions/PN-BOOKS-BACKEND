<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'status',
        'employee_id',
        'employee_code',
        'agent_no',
        'department_id',
        'date_of_birth',
        'date_of_joining',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth'     => 'date',
        'date_of_joining'   => 'date',
        'password'          => 'hashed',
    ];

    // avatar_url is the single source of truth the header/profile UI reads from.
    // Admin/employee accounts have no photo of their own — only author/publisher do.
    protected $appends = ['avatar_url'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function authorProfile()
    {
        return $this->hasOne(AuthorProfile::class);
    }

    public function publisherProfile()
    {
        return $this->hasOne(PublisherProfile::class);
    }

    public function customerProfile()
    {
        return $this->hasOne(CustomerProfile::class);
    }

    /**
     * author    -> author_profiles.profile_photo
     * publisher -> publisher_profiles.logo
     * admin/employee -> no photo, falls back to the placeholder icon
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->hasRole('author') && $this->authorProfile?->profile_photo) {
            return $this->authorProfile->profile_photo_url;
        }

        if ($this->hasRole('publisher') && $this->publisherProfile?->logo) {
            return $this->publisherProfile->logo_url;
        }

        return null;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
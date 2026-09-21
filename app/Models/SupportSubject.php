<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportSubject extends Model
{
    protected $fillable = ['name', 'notify_email', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function tickets()
    {
        return $this->hasMany(SupportTicket::class, 'subject_id');
    }
}
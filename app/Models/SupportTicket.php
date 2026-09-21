<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
   protected $fillable = [
    'user_id', 'name', 'email', 'subject_id', 'subject', 'message',
    'status', 'admin_reply', 'replied_by', 'replied_at',
];

public function subjectMaster()
{
    return $this->belongsTo(SupportSubject::class, 'subject_id');
}

    protected $casts = [
        'replied_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function repliedBy()
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }
}
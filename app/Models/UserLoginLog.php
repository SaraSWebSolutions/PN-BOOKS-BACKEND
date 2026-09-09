<?php
// app/Models/UserLoginLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLoginLog extends Model
{
    protected $fillable = [
        'user_id', 'ip_address', 'user_agent',
        'login_at', 'logout_at', 'duration_seconds',
    ];

    protected $casts = [
        'login_at'  => 'datetime',
        'logout_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getIsActiveAttribute(): bool
    {
        return is_null($this->logout_at);
    }

    public function getDurationHumanAttribute(): string
    {
        $seconds = $this->duration_seconds
            ?? ($this->logout_at ? $this->login_at->diffInSeconds($this->logout_at) : now()->diffInSeconds($this->login_at));

        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);

        if ($h > 0) return "{$h}h {$m}m";
        return "{$m}m";
    }
}
<?php
// app/Listeners/LogUserLogin.php

namespace App\Listeners;

use App\Models\UserLoginLog;
use Illuminate\Auth\Events\Login;

class LogUserLogin
{
    public function handle(Login $event): void
    {
        UserLoginLog::create([
            'user_id'    => $event->user->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'login_at'   => now(),
        ]);
    }
}
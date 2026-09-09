<?php
// app/Listeners/LogUserLogout.php

namespace App\Listeners;

use App\Models\UserLoginLog;
use Illuminate\Auth\Events\Logout;

class LogUserLogout
{
    public function handle(Logout $event): void
    {
        if (!$event->user) return;

        $log = UserLoginLog::where('user_id', $event->user->id)
            ->whereNull('logout_at')
            ->latest('login_at')
            ->first();

        if ($log) {
            $log->update([
                'logout_at'        => now(),
                'duration_seconds' => $log->login_at->diffInSeconds(now()),
            ]);
        }
    }
}
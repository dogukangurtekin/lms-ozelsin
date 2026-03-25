<?php

namespace App\Services;

use App\Models\LoginLog;
use App\Models\User;

class LoginLogService
{
    public function store(User $user, ?string $ip, ?string $agent): LoginLog
    {
        return LoginLog::create([
            'user_id' => $user->id,
            'ip_address' => $ip,
            'user_agent' => $agent,
            'logged_in_at' => now(),
        ]);
    }
}

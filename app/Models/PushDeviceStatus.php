<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushDeviceStatus extends Model
{
    protected $fillable = [
        'user_id',
        'device_key',
        'device_label',
        'platform',
        'browser',
        'user_agent',
        'permission_state',
        'subscription_endpoint',
        'is_standalone',
        'last_seen_at',
    ];

    protected $casts = [
        'is_standalone' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationLog extends Model
{
    protected $fillable = [
        'user_id',
        'channel',
        'title',
        'body',
        'target_type',
        'target_count',
        'success_count',
        'failed_count',
        'status',
        'target_summary',
        'error_message',
        'url',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reads(): HasMany
    {
        return $this->hasMany(NotificationLogRead::class);
    }

    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        if ($user->hasRole('admin')) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($user) {
            $inner->where('target_type', 'all')
                ->orWhere('target_summary', 'all subscribed users')
                ->orWhereRaw(
                    "CONCAT(',', REPLACE(COALESCE(target_summary, ''), 'user_ids:', ''), ',') LIKE ?",
                    ['%,' . $user->id . ',%']
                );
        });
    }
}

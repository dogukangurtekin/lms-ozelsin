<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'notification_type',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function definitions(): array
    {
        return config('notification-preferences.types', []);
    }

    public static function definition(string $type): array
    {
        return self::definitions()[$type] ?? [];
    }

    public static function isLocked(string $type): bool
    {
        return (bool) (self::definition($type)['locked'] ?? false);
    }

    public static function defaultEnabled(string $type): bool
    {
        return (bool) (self::definition($type)['default'] ?? true);
    }
}

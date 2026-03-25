<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class WhatsappLog extends Model
{
    protected $fillable = [
        'message_id', 'provider', 'provider_message_id', 'receiver_phone', 'status', 'response_payload', 'error_message', 'sent_at'
    ];

    protected $casts = ['sent_at' => 'datetime'];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}

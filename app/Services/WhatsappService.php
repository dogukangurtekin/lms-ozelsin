<?php

namespace App\Services;

use App\Jobs\SendWhatsappMessageJob;
use App\Models\Message;
use App\Models\WhatsappLog;

class WhatsappService
{
    public function queueMessage(array $data): Message
    {
        $message = Message::create([
            'sender_id' => $data['sender_id'],
            'receiver_id' => $data['receiver_id'],
            'type' => $data['type'],
            'channel' => 'whatsapp',
            'content' => $data['content'],
            'sent_at' => now(),
        ]);

        $log = WhatsappLog::create([
            'message_id' => $message->id,
            'provider' => config('services.whatsapp.provider', 'twilio'),
            'receiver_phone' => $data['receiver_phone'] ?? null,
            'status' => 'queued',
        ]);

        SendWhatsappMessageJob::dispatch($log->id);

        return $message;
    }
}

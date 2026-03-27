<?php

namespace App\Services;

use App\Jobs\SendWhatsappMessageJob;
use App\Models\Message;
use App\Models\WhatsappLog;

class WhatsappService
{
    public function queueMessage(array $data): Message
    {
        $provider = (string) config('services.whatsapp.provider', 'venom');
        $channel = 'whatsapp';
        $delaySeconds = max(0, (int) ($data['dispatch_delay_seconds'] ?? 0));
        $forceQueue = (bool) ($data['force_queue'] ?? false);
        $scheduledFor = $delaySeconds > 0 ? now()->addSeconds($delaySeconds) : null;

        $message = Message::create([
            'sender_id' => $data['sender_id'],
            'receiver_id' => $data['receiver_id'],
            'type' => $data['type'],
            'channel' => $channel,
            'content' => $data['content'],
            'sent_at' => now(),
        ]);

        $log = WhatsappLog::create([
            'message_id' => $message->id,
            'provider' => $provider,
            'receiver_phone' => $data['receiver_phone'] ?? null,
            'sender_phone' => $data['sender_phone'] ?? null,
            'scheduled_for' => $scheduledFor,
            'status' => 'queued',
        ]);

        if ($forceQueue || $delaySeconds > 0) {
            SendWhatsappMessageJob::dispatch($log->id)->delay($scheduledFor ?? now());
        } elseif ((bool) config('services.whatsapp.process_immediately', false)) {
            SendWhatsappMessageJob::dispatchSync($log->id);
        } else {
            SendWhatsappMessageJob::dispatch($log->id);
        }

        return $message;
    }
}

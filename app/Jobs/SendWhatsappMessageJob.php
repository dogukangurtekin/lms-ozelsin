<?php

namespace App\Jobs;

use App\Models\WhatsappLog;
use App\Services\Whatsapp\WhatsappProviderFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWhatsappMessageJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $whatsappLogId) {}

    public function handle(): void
    {
        $log = WhatsappLog::with('message')->find($this->whatsappLogId);

        if (! $log || ! $log->message) {
            return;
        }

        $receiver = \App\Models\User::find($log->message->receiver_id);
        if (! $receiver?->phone) {
            $log->update(['status' => 'failed', 'error_message' => 'Receiver phone is missing']);
            return;
        }

        $provider = WhatsappProviderFactory::make($log->provider);
        $result = $provider->send($receiver->phone, $log->message->content);

        $log->update([
            'provider_message_id' => $result['provider_message_id'] ?? null,
            'receiver_phone' => $receiver->phone,
            'status' => ($result['ok'] ?? false) ? 'sent' : 'failed',
            'response_payload' => $result['payload'] ?? null,
            'error_message' => $result['error'] ?? null,
            'sent_at' => now(),
        ]);
    }
}

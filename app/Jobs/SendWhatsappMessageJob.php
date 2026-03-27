<?php

namespace App\Jobs;

use App\Models\WhatsappLog;
use App\Services\Whatsapp\WhatsappProviderFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

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

        $normalizedPhone = $this->normalizePhoneForWhatsapp((string) $receiver->phone);
        if ($normalizedPhone === null) {
            $log->update(['status' => 'failed', 'error_message' => 'Receiver phone format is invalid']);
            return;
        }

        try {
            if ($log->provider === 'venom' && ! empty($log->sender_phone)) {
                config(['services.venom.session' => $this->sessionFromPhone((string) $log->sender_phone)]);
            }

            $provider = WhatsappProviderFactory::make($log->provider);
            $result = $provider->send($normalizedPhone, (string) $log->message->content);

            $log->update([
                'provider_message_id' => $result['provider_message_id'] ?? null,
                'receiver_phone' => $normalizedPhone,
                'status' => ($result['ok'] ?? false) ? 'sent' : 'failed',
                'response_payload' => $result['payload'] ?? null,
                'error_message' => $result['error'] ?? null,
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'sent_at' => now(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        $log = WhatsappLog::find($this->whatsappLogId);
        if (! $log) {
            return;
        }

        $log->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'sent_at' => now(),
        ]);
    }

    private function normalizePhoneForWhatsapp(string $phone): ?string
    {
        $normalized = preg_replace('/[^\d+]/', '', trim($phone));
        if ($normalized === '') {
            return null;
        }

        if (str_starts_with($normalized, '+')) {
            $digits = substr($normalized, 1);
            return ctype_digit($digits) && strlen($digits) >= 10 ? '+' . $digits : null;
        }

        if (str_starts_with($normalized, '00')) {
            $normalized = '+' . substr($normalized, 2);
            $digits = substr($normalized, 1);
            return ctype_digit($digits) && strlen($digits) >= 10 ? $normalized : null;
        }

        $digits = preg_replace('/\D/', '', $normalized);
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '90' . substr($digits, 1);
        } elseif (! str_starts_with($digits, '90')) {
            $digits = '90' . $digits;
        }

        return strlen($digits) >= 12 ? '+' . $digits : null;
    }

    private function sessionFromPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        return $digits !== '' ? $digits : 'default';
    }
}

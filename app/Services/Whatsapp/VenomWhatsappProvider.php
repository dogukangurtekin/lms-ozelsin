<?php

namespace App\Services\Whatsapp;

use App\Contracts\WhatsappProviderInterface;
use Illuminate\Support\Facades\Http;

class VenomWhatsappProvider implements WhatsappProviderInterface
{
    public function send(string $to, string $message): array
    {
        $baseUrl = rtrim((string) config('services.venom.base_url'), '/');
        $session = (string) config('services.venom.session');
        $token = (string) config('services.venom.token');
        $chatId = $this->normalizeChatId($to);

        if ($baseUrl === '' || $session === '') {
            return [
                'ok' => false,
                'provider_message_id' => null,
                'payload' => null,
                'error' => 'Venom yapilandirmasi eksik: VENOM_BASE_URL ve VENOM_SESSION doldurun.',
            ];
        }

        if ($chatId === null) {
            return [
                'ok' => false,
                'provider_message_id' => null,
                'payload' => null,
                'error' => 'Alici telefon formati gecersiz.',
            ];
        }

        $payload = [
            'number' => $chatId,
            'text' => $message,
        ];

        $request = Http::timeout(30);
        if ($token !== '') {
            $request = $request->withToken($token);
        }

        $this->startSession($request, $baseUrl, $session);

        $response = $request->post("{$baseUrl}/api/{$session}/send-text", $payload);
        $json = $response->json();
        $ok = $response->successful() && ! empty($json) && (bool) data_get($json, 'ok', true);

        if (! $ok) {
            $body = (string) $response->body();
            if (str_contains($body, 'Failed to launch the browser process') || str_contains($body, 'Auto Close Called')) {
                $this->startSession($request, $baseUrl, $session);
                usleep(700000);

                $response = $request->post("{$baseUrl}/api/{$session}/send-text", $payload);
                $json = $response->json();
                $ok = $response->successful() && ! empty($json) && (bool) data_get($json, 'ok', true);
            }
        }

        return [
            'ok' => $ok,
            'provider_message_id' => (string) (data_get($json, 'id') ?? data_get($json, 'response.id') ?? ''),
            'payload' => $response->body(),
            'error' => $ok ? null : ('Venom hata: ' . $response->body()),
        ];
    }

    private function startSession($request, string $baseUrl, string $session): void
    {
        try {
            $request->post("{$baseUrl}/api/{$session}/start", []);
        } catch (\Throwable) {
            // Ignore; send-text can still succeed if session is already active.
        }
    }

    private function normalizeChatId(string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '90' . substr($digits, 1);
        }

        return strlen($digits) >= 10 ? $digits : null;
    }
}

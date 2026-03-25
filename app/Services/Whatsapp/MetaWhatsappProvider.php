<?php

namespace App\Services\Whatsapp;

use App\Contracts\WhatsappProviderInterface;
use Illuminate\Support\Facades\Http;

class MetaWhatsappProvider implements WhatsappProviderInterface
{
    public function send(string $to, string $message): array
    {
        $phoneNumberId = config('services.meta_whatsapp.phone_number_id');
        $token = config('services.meta_whatsapp.token');

        $response = Http::withToken($token)
            ->post("https://graph.facebook.com/v22.0/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => ['body' => $message],
            ]);

        return [
            'ok' => $response->successful(),
            'provider_message_id' => data_get($response->json(), 'messages.0.id'),
            'payload' => $response->body(),
            'error' => $response->successful() ? null : $response->body(),
        ];
    }
}

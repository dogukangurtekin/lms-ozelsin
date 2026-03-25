<?php

namespace App\Services\Whatsapp;

use App\Contracts\WhatsappProviderInterface;
use Illuminate\Support\Facades\Http;

class TwilioWhatsappProvider implements WhatsappProviderInterface
{
    public function send(string $to, string $message): array
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.whatsapp_from');

        $response = Http::asForm()
            ->withBasicAuth($sid, $token)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => "whatsapp:{$from}",
                'To' => "whatsapp:{$to}",
                'Body' => $message,
            ]);

        return [
            'ok' => $response->successful(),
            'provider_message_id' => $response->json('sid'),
            'payload' => $response->body(),
            'error' => $response->successful() ? null : $response->body(),
        ];
    }
}

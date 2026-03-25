<?php

namespace App\Services\Whatsapp;

use App\Contracts\WhatsappProviderInterface;

class WhatsappProviderFactory
{
    public static function make(?string $provider = null): WhatsappProviderInterface
    {
        return match ($provider ?? config('services.whatsapp.provider', 'twilio')) {
            'meta' => app(MetaWhatsappProvider::class),
            default => app(TwilioWhatsappProvider::class),
        };
    }
}

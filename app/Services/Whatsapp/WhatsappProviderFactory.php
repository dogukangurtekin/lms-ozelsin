<?php

namespace App\Services\Whatsapp;

use App\Contracts\WhatsappProviderInterface;

class WhatsappProviderFactory
{
    public static function make(?string $provider = null): WhatsappProviderInterface
    {
        $resolvedProvider = $provider ?? config('services.whatsapp.provider', 'venom');
        if ($resolvedProvider !== 'venom') {
            throw new \InvalidArgumentException('Sadece Venom Bot sağlayıcısı kullanılabilir.');
        }

        return app(VenomWhatsappProvider::class);
    }
}

<?php

namespace App\Contracts;

interface WhatsappProviderInterface
{
    public function send(string $to, string $message): array;
}

<?php

namespace App\Console\Commands;

use App\Services\PushNotificationService;
use Illuminate\Console\Command;

class SendRawPushNotification extends Command
{
    protected $signature = 'notifications:send-raw {payload}';

    protected $description = 'CLI uzerinden ham push bildirim gonderimi yapar';

    public function handle(PushNotificationService $pushNotifications): int
    {
        $payload = json_decode(base64_decode((string) $this->argument('payload'), true) ?: '', true);

        if (! is_array($payload)) {
            $this->error('Gecersiz payload');
            return self::FAILURE;
        }

        $title = (string) ($payload['title'] ?? '');
        $body = (string) ($payload['body'] ?? '');
        $url = isset($payload['url']) ? (string) $payload['url'] : null;
        $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];

        if (($payload['mode'] ?? 'users') === 'all') {
            $count = $pushNotifications->sendToAll($title, $body, $url, $meta);
        } else {
            $count = $pushNotifications->sendToUsers($payload['user_ids'] ?? [], $title, $body, $url, $meta);
        }

        $this->line((string) $count);

        return self::SUCCESS;
    }
}

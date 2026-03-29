<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\NotificationPreference;
use App\Models\PushSubscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Symfony\Component\Process\Process;

class PushNotificationService
{
    public function sendToUsers(iterable $userIds, string $title, string $body, ?string $url = null, array $meta = []): int
    {
        $targetUserIds = collect($userIds)->filter()->unique()->values();
        $notificationType = (string) ($meta['notification_type'] ?? 'system_message');
        if (! $this->canSendInCurrentRuntime()) {
            return $this->sendViaCli([
                'mode' => 'users',
                'user_ids' => $targetUserIds->all(),
                'title' => $title,
                'body' => $body,
                'url' => $url,
                'meta' => array_merge($meta, [
                    'notification_type' => $notificationType,
                    'target_type' => $meta['target_type'] ?? 'users',
                    'target_summary' => $meta['target_summary'] ?? ('user_ids:' . $targetUserIds->implode(',')),
                    'target_count' => $meta['target_count'] ?? $targetUserIds->count(),
                    'user_id' => $meta['user_id'] ?? auth()->id(),
                ]),
            ]);
        }

        $subscriptions = $this->subscriptionsForUsersByPreference($targetUserIds, $notificationType);

        return $this->sendToSubscriptions($subscriptions, $title, $body, $url, array_merge($meta, [
            'notification_type' => $notificationType,
            'target_type' => $meta['target_type'] ?? 'users',
            'target_summary' => $meta['target_summary'] ?? ('user_ids:' . $targetUserIds->implode(',')),
            'target_count' => $meta['target_count'] ?? $targetUserIds->count(),
            'user_id' => $meta['user_id'] ?? auth()->id(),
        ]));
    }

    public function sendToAll(string $title, string $body, ?string $url = null, array $meta = []): int
    {
        $notificationType = (string) ($meta['notification_type'] ?? 'system_message');
        $subscriptions = $this->subscriptionsForAllByPreference($notificationType);
        $targetCount = $subscriptions->pluck('user_id')->unique()->count();
        if (! $this->canSendInCurrentRuntime()) {
            return $this->sendViaCli([
                'mode' => 'all',
                'title' => $title,
                'body' => $body,
                'url' => $url,
                'meta' => array_merge($meta, [
                    'notification_type' => $notificationType,
                    'target_type' => $meta['target_type'] ?? 'all',
                    'target_summary' => $meta['target_summary'] ?? 'all subscribed users',
                    'target_count' => $meta['target_count'] ?? $targetCount,
                    'user_id' => $meta['user_id'] ?? auth()->id(),
                ]),
            ]);
        }

        return $this->sendToSubscriptions($subscriptions, $title, $body, $url, array_merge($meta, [
            'notification_type' => $notificationType,
            'target_type' => $meta['target_type'] ?? 'all',
            'target_summary' => $meta['target_summary'] ?? 'all subscribed users',
            'target_count' => $meta['target_count'] ?? $targetCount,
            'user_id' => $meta['user_id'] ?? auth()->id(),
        ]));
    }

    public function sendToSubscriptions(Collection $subscriptions, string $title, string $body, ?string $url = null, array $meta = []): int
    {
        if ($subscriptions->isEmpty()) {
            $this->writeLog($title, $body, $url, array_merge($meta, [
                'success_count' => 0,
                'failed_count' => 0,
                'status' => 'no_target',
                'error_message' => $meta['error_message'] ?? 'Kayitli push aboneligi bulunamadi.',
            ]));
            return 0;
        }

        $config = config('webpush.vapid');
        Validator::make($config, [
            'subject' => ['required', 'string'],
            'public_key' => ['required', 'string'],
            'private_key' => ['required', 'string'],
        ])->validate();

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => $config['subject'],
                'publicKey' => $config['public_key'],
                'privateKey' => $config['private_key'],
            ],
        ]);

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url ?: route('dashboard'),
            'icon' => asset('assets/logo.png'),
            'badge' => asset('assets/logo.png'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        foreach ($subscriptions as $subscription) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'publicKey' => $subscription->public_key,
                    'authToken' => $subscription->auth_token,
                    'contentEncoding' => $subscription->content_encoding ?: 'aesgcm',
                ]),
                $payload
            );
        }

        $sentCount = 0;
        $failedCount = 0;
        $lastError = null;

        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $sentCount++;
                continue;
            }

            $failedCount++;
            $lastError = $report->getReason();

            $endpoint = $report->getRequest()?->getUri()?->__toString();
            if ($endpoint) {
                PushSubscription::query()->where('endpoint', $endpoint)->delete();
            }
        }

        $this->writeLog($title, $body, $url, array_merge($meta, [
            'success_count' => $sentCount,
            'failed_count' => $failedCount,
            'status' => $failedCount > 0 && $sentCount === 0 ? 'failed' : ($failedCount > 0 ? 'partial' : 'sent'),
            'error_message' => $lastError,
        ]));

        return $sentCount;
    }

    private function writeLog(string $title, string $body, ?string $url, array $meta = []): void
    {
        NotificationLog::create([
            'user_id' => $meta['user_id'] ?? auth()->id(),
            'channel' => 'push',
            'title' => $title,
            'body' => $body,
            'target_type' => $meta['target_type'] ?? 'users',
            'target_count' => (int) ($meta['target_count'] ?? 0),
            'success_count' => (int) ($meta['success_count'] ?? 0),
            'failed_count' => (int) ($meta['failed_count'] ?? 0),
            'status' => (string) ($meta['status'] ?? 'sent'),
            'target_summary' => $meta['target_summary'] ?? null,
            'error_message' => $meta['error_message'] ?? null,
            'url' => $url,
            'sent_at' => now(),
        ]);
    }

    private function canSendInCurrentRuntime(): bool
    {
        return extension_loaded('curl');
    }

    private function subscriptionsForUsersByPreference(Collection $userIds, string $notificationType): Collection
    {
        if ($userIds->isEmpty()) {
            return collect();
        }

        if (NotificationPreference::isLocked($notificationType)) {
            return PushSubscription::query()
                ->whereIn('user_id', $userIds)
                ->get();
        }

        $disabledUserIds = NotificationPreference::query()
            ->whereIn('user_id', $userIds)
            ->where('notification_type', $notificationType)
            ->where('is_enabled', false)
            ->pluck('user_id');

        return PushSubscription::query()
            ->whereIn('user_id', $userIds->diff($disabledUserIds)->values())
            ->get();
    }

    private function subscriptionsForAllByPreference(string $notificationType): Collection
    {
        $query = PushSubscription::query();

        if (NotificationPreference::isLocked($notificationType)) {
            return $query->get();
        }

        $disabledUserIds = NotificationPreference::query()
            ->where('notification_type', $notificationType)
            ->where('is_enabled', false)
            ->pluck('user_id');

        if ($disabledUserIds->isNotEmpty()) {
            $query->whereNotIn('user_id', $disabledUserIds);
        }

        return $query->get();
    }

    private function sendViaCli(array $payload): int
    {
        $phpBinary = 'C:\\tools\\php85\\php.exe';
        $commandPayload = base64_encode(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $process = new Process([
            $phpBinary,
            'artisan',
            'notifications:send-raw',
            $commandPayload,
        ], base_path());
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
            $this->writeLog(
                (string) ($payload['title'] ?? 'Push'),
                (string) ($payload['body'] ?? ''),
                isset($payload['url']) ? (string) $payload['url'] : null,
                array_merge($meta, [
                    'success_count' => 0,
                    'failed_count' => (int) ($meta['target_count'] ?? 0),
                    'status' => 'failed',
                    'error_message' => trim($process->getErrorOutput()) ?: trim($process->getOutput()) ?: 'CLI push gonderimi basarisiz.',
                ])
            );

            return 0;
        }

        return (int) trim($process->getOutput());
    }
}

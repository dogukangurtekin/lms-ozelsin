<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\PushSubscription as PushSubscriptionModel;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PushNotificationController extends Controller
{
    public function __construct(private readonly PushNotificationService $pushNotifications)
    {
    }

    public function index(Request $request)
    {
        $pushSubscriptionCount = 0;

        if (Schema::hasTable('push_subscriptions')) {
            $pushSubscriptionCount = PushSubscriptionModel::query()
                ->where('user_id', $request->user()->id)
                ->count();
        }

        $statusFilter = (string) $request->input('status', '');
        $search = trim((string) $request->input('q', ''));
        $failedOnly = $request->boolean('failed_only');

        $notificationLogs = NotificationLog::query()
            ->with('user:id,name')
            ->when($statusFilter !== '', fn ($query) => $query->where('status', $statusFilter))
            ->when($failedOnly, fn ($query) => $query->whereIn('status', ['failed', 'partial', 'no_target']))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('body', 'like', "%{$search}%")
                        ->orWhere('target_summary', 'like', "%{$search}%")
                        ->orWhere('error_message', 'like', "%{$search}%");
                });
            })
            ->latest('sent_at')
            ->limit(50)
            ->get();

        $failedLogs = NotificationLog::query()
            ->with('user:id,name')
            ->whereIn('status', ['failed', 'partial', 'no_target'])
            ->latest('sent_at')
            ->limit(20)
            ->get();

        return view('notifications.index', compact(
            'pushSubscriptionCount',
            'notificationLogs',
            'failedLogs',
            'statusFilter',
            'search',
            'failedOnly'
        ));
    }

    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
            'contentEncoding' => ['nullable', 'string'],
        ]);

        PushSubscriptionModel::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'user_id' => $request->user()->id,
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => $data['contentEncoding'] ?? 'aesgcm',
            ]
        );

        return response()->json([
            'ok' => true,
            'message' => 'Bildirim aboneligi kaydedildi.',
        ]);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => ['required', 'url'],
        ]);

        PushSubscriptionModel::query()
            ->where('user_id', $request->user()->id)
            ->where('endpoint', $request->string('endpoint'))
            ->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Bildirim aboneligi kaldirildi.',
        ]);
    }

    public function publicKey(): JsonResponse
    {
        $publicKey = (string) config('webpush.vapid.public_key');

        if ($publicKey === '') {
            return response()->json([
                'ok' => false,
                'message' => 'VAPID public key tanimli degil.',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'publicKey' => $publicKey,
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:240'],
            'url' => ['nullable', 'url', 'max:500'],
            'audience' => ['required', 'in:self,all'],
        ]);

        $sentCount = $validated['audience'] === 'self'
            ? $this->pushNotifications->sendToUsers(
                [$request->user()->id],
                $validated['title'],
                $validated['body'],
                $validated['url'] ?? route('dashboard')
            )
            : $this->pushNotifications->sendToAll(
                $validated['title'],
                $validated['body'],
                $validated['url'] ?? route('dashboard')
            );

        if ($sentCount === 0) {
            throw ValidationException::withMessages([
                'audience' => 'Secilen hedef icin kayitli bildirim aboneligi yok.',
            ]);
        }

        return back()->with('success', 'Push bildirimi gonderildi.');
    }

    public function resend(Request $request, NotificationLog $notificationLog): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $targetType = (string) ($notificationLog->target_type ?? '');
        $targetSummary = (string) ($notificationLog->target_summary ?? '');
        $meta = [
            'target_type' => $targetType !== '' ? $targetType : 'resend',
            'target_summary' => $targetSummary !== '' ? $targetSummary : 'resend',
            'user_id' => $request->user()->id,
        ];

        if ($targetType === 'all' || $targetSummary === 'all subscribed users') {
            $sentCount = $this->pushNotifications->sendToAll(
                $notificationLog->title,
                $notificationLog->body,
                $notificationLog->url,
                $meta
            );
        } elseif (str_starts_with($targetSummary, 'user_ids:')) {
            $userIds = collect(explode(',', substr($targetSummary, 9)))
                ->map(fn ($id) => (int) trim($id))
                ->filter()
                ->values();

            if ($userIds->isEmpty()) {
                return back()->withErrors(['resend' => 'Tekrar gonderim icin gecerli hedef kullanici bulunamadi.']);
            }

            $meta['target_count'] = $userIds->count();
            $sentCount = $this->pushNotifications->sendToUsers(
                $userIds,
                $notificationLog->title,
                $notificationLog->body,
                $notificationLog->url,
                $meta
            );
        } else {
            return back()->withErrors(['resend' => 'Bu log kaydi tekrar gonderim icin desteklenmiyor.']);
        }

        if ($sentCount === 0) {
            return back()->withErrors(['resend' => 'Tekrar gonderimde ulasilabilir abone bulunamadi.']);
        }

        return back()->with('success', 'Bildirim tekrar gonderildi.');
    }

    public function broadcastSystemNotice(string $title, string $body, ?string $url = null): void
    {
        $this->pushNotifications->sendToAll($title, $body, $url ?? route('dashboard'));
    }
}

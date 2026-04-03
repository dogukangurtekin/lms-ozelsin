<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\NotificationLogRead;
use App\Models\NotificationPreference;
use App\Models\PushDeviceStatus;
use App\Models\PushSubscription as PushSubscriptionModel;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PushNotificationController extends Controller
{
    public function __construct(private readonly PushNotificationService $pushNotifications)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $pushSubscriptionCount = 0;
        $notificationLogs = collect();
        $failedLogs = collect();
        $isAdmin = $user->hasRole('admin');
        $notificationPreferenceDefinitions = $this->preferenceDefinitionsFor($user);
        $userNotificationPreferences = collect();
        $deviceStatuses = collect();
        $advancedNotificationSettings = [
            'attendance_reminder_after_start_minutes' => (int) Cache::get('notification_settings.attendance_reminder_after_start_minutes', 10),
            'attendance_last_five_enabled' => (bool) Cache::get('notification_settings.attendance_last_five_enabled', true),
        ];
        $usersForTargeting = collect();
        $roleOptions = ['admin', 'teacher', 'student', 'parent'];

        if (Schema::hasTable('push_subscriptions')) {
            $pushSubscriptionCount = PushSubscriptionModel::query()
                ->where('user_id', $user->id)
                ->count();
        }

        if (Schema::hasTable('notification_preferences')) {
            $userNotificationPreferences = NotificationPreference::query()
                ->where('user_id', $user->id)
                ->whereIn('notification_type', array_keys($notificationPreferenceDefinitions))
                ->pluck('is_enabled', 'notification_type');
        }

        if (Schema::hasTable('push_device_statuses')) {
            $deviceStatuses = PushDeviceStatus::query()
                ->where('user_id', $user->id)
                ->latest('last_seen_at')
                ->get();
        }

        $statusFilter = (string) $request->input('status', '');
        $search = trim((string) $request->input('q', ''));
        $failedOnly = $request->boolean('failed_only');
        $hasLogFilter = $statusFilter !== '' || $search !== '' || $failedOnly;

        if ($isAdmin && Schema::hasTable('notification_logs')) {
            if ($hasLogFilter) {
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
                    ->limit(100)
                    ->get();
            }

            $failedLogs = NotificationLog::query()
                ->with('user:id,name')
                ->whereIn('status', ['failed', 'partial', 'no_target'])
                ->latest('sent_at')
                ->limit(20)
                ->get();
        }
        if ($isAdmin) {
            $usersForTargeting = User::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->limit(250)
                ->get();
        }

        return view('notifications.index', compact(
            'pushSubscriptionCount',
            'notificationLogs',
            'failedLogs',
            'statusFilter',
            'search',
            'failedOnly',
            'hasLogFilter',
            'deviceStatuses',
            'notificationPreferenceDefinitions',
            'userNotificationPreferences',
            'advancedNotificationSettings',
            'usersForTargeting',
            'roleOptions'
        ));
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $definitions = $this->preferenceDefinitionsFor($request->user());
        $validated = $request->validate([
            'preferences' => ['nullable', 'array'],
        ]);

        $inputPreferences = collect($validated['preferences'] ?? []);
        $rows = collect($definitions)
            ->reject(fn (array $definition) => (bool) ($definition['locked'] ?? false))
            ->map(function (array $definition, string $type) use ($inputPreferences, $request) {
                return [
                    'user_id' => $request->user()->id,
                    'notification_type' => $type,
                    'is_enabled' => $inputPreferences->has($type),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })
            ->values()
            ->all();

        if (Schema::hasTable('notification_preferences') && $rows !== []) {
            NotificationPreference::query()->upsert(
                $rows,
                ['user_id', 'notification_type'],
                ['is_enabled', 'updated_at']
            );
        }

        return back()->with('success', 'Bildirim ayarlari guncellendi.');
    }

    public function subscribe(Request $request): JsonResponse
    {
        if (! Schema::hasTable('push_subscriptions')) {
            return response()->json([
                'ok' => false,
                'message' => 'Push abonelik tablosu henuz olusturulmamis.',
            ], 503);
        }

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
        if (! Schema::hasTable('push_subscriptions')) {
            return response()->json([
                'ok' => false,
                'message' => 'Push abonelik tablosu henuz olusturulmamis.',
            ], 503);
        }

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

    public function deviceStatus(Request $request): JsonResponse
    {
        if (! Schema::hasTable('push_device_statuses')) {
            return response()->json([
                'ok' => false,
                'message' => 'Cihaz durum tablosu henuz olusturulmamis.',
            ], 503);
        }

        $data = $request->validate([
            'device_key' => ['required', 'string', 'max:120'],
            'device_label' => ['nullable', 'string', 'max:160'],
            'platform' => ['nullable', 'string', 'max:40'],
            'browser' => ['nullable', 'string', 'max:40'],
            'user_agent' => ['nullable', 'string', 'max:2000'],
            'permission_state' => ['required', 'in:default,granted,denied'],
            'subscription_endpoint' => ['nullable', 'url'],
            'is_standalone' => ['nullable', 'boolean'],
        ]);

        PushDeviceStatus::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'device_key' => $data['device_key'],
            ],
            [
                'device_label' => $data['device_label'] ?? null,
                'platform' => $data['platform'] ?? null,
                'browser' => $data['browser'] ?? null,
                'user_agent' => $data['user_agent'] ?? null,
                'permission_state' => $data['permission_state'],
                'subscription_endpoint' => $data['subscription_endpoint'] ?? null,
                'is_standalone' => (bool) ($data['is_standalone'] ?? false),
                'last_seen_at' => now(),
            ]
        );

        return response()->json([
            'ok' => true,
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:240'],
            'url' => ['nullable', 'url', 'max:500'],
            'notification_type' => ['required', 'string', 'max:80'],
            'audience' => ['required', 'in:self,all,role,users'],
            'role_name' => ['nullable', 'in:admin,teacher,student,parent'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $meta = ['notification_type' => $validated['notification_type']];
        $audience = $validated['audience'];
        if ($audience === 'self') {
            $sentCount = $this->pushNotifications->sendToUsers(
                [$request->user()->id],
                $validated['title'],
                $validated['body'],
                $validated['url'] ?? route('dashboard'),
                $meta
            );
        } elseif ($audience === 'all') {
            $sentCount = $this->pushNotifications->sendToAll(
                $validated['title'],
                $validated['body'],
                $validated['url'] ?? route('dashboard'),
                $meta
            );
        } elseif ($audience === 'role') {
            $roleName = (string) ($validated['role_name'] ?? '');
            $targetIds = User::query()
                ->whereHas('roles', fn ($q) => $q->where('name', $roleName))
                ->pluck('id')
                ->all();
            $meta['target_type'] = 'role';
            $meta['target_summary'] = 'role:'.$roleName;
            $meta['target_count'] = count($targetIds);
            $sentCount = $this->pushNotifications->sendToUsers(
                $targetIds,
                $validated['title'],
                $validated['body'],
                $validated['url'] ?? route('dashboard'),
                $meta
            );
        } else {
            $targetIds = collect($validated['user_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
            $meta['target_type'] = 'users';
            $meta['target_summary'] = 'user_ids:'.implode(',', $targetIds);
            $meta['target_count'] = count($targetIds);
            $sentCount = $this->pushNotifications->sendToUsers(
                $targetIds,
                $validated['title'],
                $validated['body'],
                $validated['url'] ?? route('dashboard'),
                $meta
            );
        }

        if ($sentCount === 0) {
            throw ValidationException::withMessages([
                'audience' => 'Secilen hedef icin kayitli bildirim aboneligi yok.',
            ]);
        }

        return back()->with('success', 'Push bildirimi gonderildi.');
    }

    public function destroy(Request $request, NotificationLog $notificationLog): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        if (Schema::hasTable('notification_log_reads')) {
            NotificationLogRead::query()
                ->where('notification_log_id', $notificationLog->id)
                ->delete();
        }

        $notificationLog->delete();

        return back()->with('success', 'Bildirim kaydı silindi.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $validated = $request->validate([
            'attendance_reminder_after_start_minutes' => ['required', 'integer', 'min:1', 'max:180'],
            'attendance_last_five_enabled' => ['nullable', 'in:0,1'],
        ]);

        Cache::forever('notification_settings.attendance_reminder_after_start_minutes', (int) $validated['attendance_reminder_after_start_minutes']);
        Cache::forever('notification_settings.attendance_last_five_enabled', $request->boolean('attendance_last_five_enabled'));

        return back()->with('success', 'Bildirim yönetim ayarları güncellendi.');
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

    public function markAsRead(Request $request, NotificationLog $notificationLog): JsonResponse
    {
        abort_unless(
            NotificationLog::query()
                ->whereKey($notificationLog->getKey())
                ->visibleToUser($request->user())
                ->exists(),
            404
        );

        if (! Schema::hasTable('notification_log_reads')) {
            return response()->json([
                'ok' => false,
                'message' => 'Bildirim okunma tablosu hazir degil.',
            ], 503);
        }

        NotificationLogRead::query()->updateOrCreate(
            [
                'notification_log_id' => $notificationLog->id,
                'user_id' => $request->user()->id,
            ],
            [
                'read_at' => now(),
            ]
        );

        return response()->json([
            'ok' => true,
        ]);
    }

    public function broadcastSystemNotice(string $title, string $body, ?string $url = null): void
    {
        $this->pushNotifications->sendToAll($title, $body, $url ?? route('dashboard'), [
            'notification_type' => 'system_message',
        ]);
    }

    private function preferenceDefinitionsFor($user): array
    {
        $roleNames = $user->roles()->pluck('name')->all();

        return collect(NotificationPreference::definitions())
            ->filter(function (array $definition) use ($roleNames) {
                $roles = $definition['roles'] ?? [];

                return $roles === [] || array_intersect($roles, $roleNames) !== [];
            })
            ->all();
    }
}

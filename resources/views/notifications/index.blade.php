<x-app-layout>
    <x-slot name="header">Bildirimler</x-slot>

    @php
        $resolvedPreferences = collect($notificationPreferenceDefinitions)->map(function ($definition, $type) use ($userNotificationPreferences) {
            $locked = (bool) ($definition['locked'] ?? false);
            $enabled = $locked
                ? true
                : (bool) ($userNotificationPreferences[$type] ?? ($definition['default'] ?? true));

            return $definition + [
                'type' => $type,
                'enabled' => $enabled,
                'locked' => $locked,
            ];
        })->values();
    @endphp

    <div class="space-y-6 overflow-x-hidden">
        @if(session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="grid gap-6 overflow-x-hidden {{ auth()->user()?->hasRole('admin') ? 'lg:grid-cols-[minmax(0,1.3fr)_minmax(320px,0.9fr)]' : '' }}">
            <article class="lms-panel">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">PWA ve Push Bildirimleri</h3>
                        <p class="mt-1 text-sm text-slate-600">Bu cihaz icin push izni verip size gonderilebilecek bildirim turlerini yonetebilirsiniz.</p>
                    </div>
                    <div class="flex flex-wrap gap-2 max-sm:grid max-sm:grid-cols-2">
                        <button type="button" data-pwa-install class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                            Uygulamayi Kur
                        </button>
                        <button type="button" data-push-enable class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Bildirimleri Ac
                        </button>
                        <button type="button" data-push-disable class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Kapat
                        </button>
                        <button type="button" onclick="document.getElementById('notification-settings-dialog').showModal()" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Bildirim Ayarlari
                        </button>
                        <button type="button" onclick="document.getElementById('push-devices-dialog').showModal()" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Izin Veren Cihazlar
                        </button>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-[220px_minmax(0,1fr)]">
                    <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                        <div class="text-xs uppercase tracking-wide text-slate-400">Cihaz Durumu</div>
                        <div class="mt-1 font-semibold" data-push-count data-count="{{ $pushSubscriptionCount }}">{{ $pushSubscriptionCount > 0 ? $pushSubscriptionCount.' cihaz bagli' : 'Bagli cihaz yok' }}</div>
                        <div class="mt-3 flex items-center gap-2">
                            <span class="text-xs uppercase tracking-wide text-slate-400">Anlik Durum</span>
                            <span
                                data-push-live-state
                                class="inline-flex rounded-full border border-slate-200 bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600"
                            >
                                Kontrol ediliyor
                            </span>
                        </div>
                    </div>
                    <div data-push-status class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600">
                        Bildirimleri acarak odev, gorusme ve sistem duyurularini anlik alabilirsiniz.
                    </div>
                </div>

                <div data-pwa-install-status class="mt-3 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600">
                    iPhone kullaniyorsaniz Safari uzerinden Paylas > Ana Ekrana Ekle ile kurulumu tamamlayin. Android cihazlarda kurulum butonu otomatik pencereyi acabilir.
                </div>

                <div class="mt-3 rounded-lg border border-sky-200 bg-sky-50 px-3 py-3 text-sm text-sky-900">
                    Push destek hedefi: Android icin Chrome, Edge, Firefox, Opera ve Samsung Internet; Windows ve Linux icin Chrome, Edge ve Firefox; macOS icin Safari, Chrome ve Edge; iPhone ve iPad icin Ana Ekrana eklenmis web uygulamasi.
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    @foreach($resolvedPreferences as $preference)
                        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-medium text-slate-900">{{ $preference['label'] }}</div>
                                    <p class="mt-1 text-xs text-slate-500">{{ $preference['description'] }}</p>
                                </div>
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $preference['enabled'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $preference['locked'] ? 'Zorunlu' : ($preference['enabled'] ? 'Acik' : 'Kapali') }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </article>

            @if(auth()->user()?->hasRole('admin'))
                <article class="lms-panel">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="lms-panel-title">Push Bildirim Gonder</h3>
                        <span class="text-xs text-slate-500">Sadece admin gorebilir</span>
                    </div>
                    <form method="POST" action="{{ route('push.send') }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Baslik</label>
                            <input type="text" name="title" value="{{ old('title', 'Ozelsin Bilgilendirme') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" required>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Mesaj</label>
                            <textarea name="body" rows="3" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" required>{{ old('body', 'Yeni duyuru ve sistem bilgilendirmeleri burada gorunur.') }}</textarea>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Tiklanacak Link</label>
                            <input type="url" name="url" value="{{ old('url', route('dashboard')) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Bildirim Tipi</label>
                            <select name="notification_type" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                <option value="system_message">Sistem İçi</option>
                                <option value="attendance_reminder">Yoklama Hatırlatma</option>
                                <option value="meeting_created">Görüşme</option>
                                <option value="assignment_created">Ödev</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Hedef</label>
                            <select name="audience" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" data-audience-select>
                                <option value="self">Sadece benim cihazlarim</option>
                                <option value="all">Tum kullanicilarin cihazlari</option>
                                <option value="role">Sadece rol bazli</option>
                                <option value="users">Sistem ici ozel kullanicilar</option>
                            </select>
                        </div>
                        <div class="hidden" data-role-target-wrap>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Rol Hedefi</label>
                            <select name="role_name" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                @foreach(($roleOptions ?? []) as $roleName)
                                    <option value="{{ $roleName }}">{{ ucfirst($roleName) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="hidden" data-users-target-wrap>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Özel Kullanıcılar</label>
                            <select name="user_ids[]" multiple class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm min-h-[120px]">
                                @foreach(($usersForTargeting ?? collect()) as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-slate-500">Ctrl/Cmd ile çoklu seçim yapabilirsiniz.</p>
                        </div>
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                            Bildirim Gonder
                        </button>
                    </form>
                </article>
            @endif
        </section>

        @if(auth()->user()?->hasRole('admin'))
            <section class="lms-panel">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="lms-panel-title">Bildirim Yönetim Ayarları</h3>
                </div>
                <form method="POST" action="{{ route('notifications.settings.update') }}" class="grid gap-4 md:grid-cols-3">
                    @csrf
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Yoklama Bildirim Süresi (dk)</label>
                        <input type="number" min="1" max="180" name="attendance_reminder_after_start_minutes" value="{{ (int) ($advancedNotificationSettings['attendance_reminder_after_start_minutes'] ?? 10) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <label class="inline-flex items-center gap-2 pt-7 text-sm text-slate-700">
                        <input type="hidden" name="attendance_last_five_enabled" value="0">
                        <input type="checkbox" name="attendance_last_five_enabled" value="1" @checked((bool) ($advancedNotificationSettings['attendance_last_five_enabled'] ?? true)) class="rounded border-slate-300">
                        Son 5 dakika hatırlatması da açık olsun
                    </label>
                    <div class="flex items-end">
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">AyarlarÄ± Kaydet</button>
                    </div>
                </form>
            </section>
        @endif

        @if(auth()->user()?->hasRole('admin'))
        <section class="lms-panel overflow-x-hidden">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="lms-panel-title">Bildirim Gecmisi</h3>
                        <p class="text-xs text-slate-500">Son 50 push bildirimi ve sonuc kayitlari</p>
                    </div>
                </div>

                <form method="GET" action="{{ route('notifications.index') }}" class="mb-4 grid gap-3 md:grid-cols-[minmax(0,1.2fr)_220px_180px_auto]">
                    <input type="text" name="q" value="{{ $search }}" placeholder="Baslik, mesaj, hedef veya hata ara" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    <select name="status" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Tum durumlar</option>
                        <option value="sent" @selected($statusFilter === 'sent')>Basarili</option>
                        <option value="partial" @selected($statusFilter === 'partial')>Kismi</option>
                        <option value="failed" @selected($statusFilter === 'failed')>Hatali</option>
                        <option value="no_target" @selected($statusFilter === 'no_target')>Hedef yok</option>
                    </select>
                    <label class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700">
                        <input type="checkbox" name="failed_only" value="1" @checked($failedOnly)>
                        Sadece hatalilar
                    </label>
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                        Filtrele
                    </button>
                </form>

                @if($hasLogFilter)
                    <div class="overflow-x-auto">
                        <table class="lms-table">
                            <thead>
                                <tr>
                                    <th>Tarih</th>
                                    <th>Baslik</th>
                                    <th>Hedef</th>
                                    <th>Durum</th>
                                    <th>Basarili</th>
                                    <th>Hatali</th>
                                    <th>Detay</th>
                                    <th>Islem</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($notificationLogs as $log)
                                    <tr>
                                        <td>{{ optional($log->sent_at)->format('d.m.Y H:i') }}</td>
                                        <td>
                                            <div class="font-medium text-slate-800">{{ $log->title }}</div>
                                            <div class="text-xs text-slate-500">{{ $log->body }}</div>
                                        </td>
                                        <td>
                                            <div>{{ $log->target_type ?: '-' }}</div>
                                            <div class="text-xs text-slate-500">{{ $log->target_summary ?: '-' }}</div>
                                        </td>
                                        <td>
                                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold
                                                {{ $log->status === 'sent' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                                {{ $log->status === 'partial' ? 'bg-amber-100 text-amber-700' : '' }}
                                                {{ $log->status === 'failed' || $log->status === 'no_target' ? 'bg-rose-100 text-rose-700' : '' }}">
                                                {{ $log->status }}
                                            </span>
                                        </td>
                                        <td>{{ $log->success_count }}</td>
                                        <td>{{ $log->failed_count }}</td>
                                        <td class="text-xs text-slate-500">
                                            <div>Gonderen: {{ $log->user?->name ?? '-' }}</div>
                                            <div>{{ $log->error_message ?: '-' }}</div>
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <form method="POST" action="{{ route('notifications.resend', $log) }}">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">
                                                        Tekrar Gonder
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('notifications.destroy', $log) }}" onsubmit="return confirm('Bu bildirim kaydÄ± silinsin mi?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-rose-300 bg-white px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                                        Sil
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">Filtreye uygun bildirim kaydi bulunamadi.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        Tüm bildirim listesi varsayılan olarak gizli. Üstte filtre seçip "Filtrele" dediğinizde listelenir.
                    </div>
                @endif
            </section>
        @endif
    </div>

    <dialog id="notification-settings-dialog" class="backdrop:bg-slate-900/50 w-[calc(100vw-1.5rem)] max-w-2xl rounded-2xl border border-slate-200 p-0 max-sm:mx-auto">
        <form method="dialog" class="border-b border-slate-200 px-4 py-4 sm:px-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Bildirim Ayarlari</h3>
                    <p class="mt-1 text-sm text-slate-500">Sistemin size gonderebildigi bildirim turlerini acip kapatabilirsiniz.</p>
                </div>
                <button type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Kapat
                </button>
            </div>
        </form>

        <form method="POST" action="{{ route('notifications.preferences.update') }}" class="px-4 py-5 sm:px-6">
            @csrf
            <div class="space-y-4">
                @foreach($resolvedPreferences as $preference)
                    <label class="flex items-start justify-between gap-4 rounded-xl border border-slate-200 px-4 py-4 max-sm:flex-col">
                        <div>
                            <div class="font-medium text-slate-900">{{ $preference['label'] }}</div>
                            <p class="mt-1 text-sm text-slate-500">{{ $preference['description'] }}</p>
                            @if($preference['locked'])
                                <p class="mt-2 text-xs font-semibold text-slate-400">Bu bildirim zorunludur ve kapatilamaz.</p>
                            @endif
                        </div>
                        <div class="pt-1 max-sm:self-end">
                            <input
                                type="checkbox"
                                name="preferences[{{ $preference['type'] }}]"
                                value="1"
                                class="h-5 w-5 rounded border-slate-300 text-slate-900 focus:ring-slate-800"
                                @checked($preference['enabled'])
                                @disabled($preference['locked'])
                            >
                        </div>
                    </label>
                @endforeach
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 max-sm:flex-col-reverse max-sm:items-stretch">
                <button type="button" onclick="document.getElementById('notification-settings-dialog').close()" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Vazgec
                </button>
                <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                    Ayarlari Kaydet
                </button>
            </div>
        </form>
    </dialog>

    <dialog id="push-devices-dialog" class="backdrop:bg-slate-900/50 w-[calc(100vw-1.5rem)] max-w-3xl rounded-2xl border border-slate-200 p-0 max-sm:mx-auto">
        <form method="dialog" class="border-b border-slate-200 px-4 py-4 sm:px-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Push Cihazlari</h3>
                    <p class="mt-1 text-sm text-slate-500">Sadece bu hesaba girip durumunu bildiren cihazlar listelenir.</p>
                </div>
                <button type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Kapat
                </button>
            </div>
        </form>

        <div class="max-h-[75vh] overflow-y-auto px-4 py-5 sm:px-6">
            <div class="mb-4 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
                Tarayici guvenligi nedeniyle sadece sisteme ugrayip durumunu paylasan cihazlari gorebiliriz. Hic giris yapmayan veya hic durum bildirmeyen cihazlar listelenmez.
            </div>

            <div class="space-y-3">
                @forelse($deviceStatuses as $device)
                    @php
                        $isEnabled = $device->permission_state === 'granted' && filled($device->subscription_endpoint);
                    @endphp
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4 class="text-sm font-semibold text-slate-900">{{ $device->device_label ?: 'Bilinmeyen cihaz' }}</h4>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $isEnabled ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $isEnabled ? 'Acik' : 'Kapali' }}
                                    </span>
                                </div>
                                <div class="mt-3 grid gap-2 text-xs text-slate-500 sm:grid-cols-2">
                                    <div>Platform: {{ $device->platform ?: '-' }}</div>
                                    <div>Tarayici: {{ $device->browser ?: '-' }}</div>
                                    <div>Calisma: {{ $device->is_standalone ? 'PWA uygulama' : 'Tarayici sekmesi' }}</div>
                                    <div>Son gorulme: {{ optional($device->last_seen_at)->format('d.m.Y H:i') ?: '-' }}</div>
                                </div>
                                @if($device->user_agent)
                                    <div class="mt-3 break-words rounded-xl bg-slate-50 px-3 py-2 text-xs text-slate-500">
                                        {{ $device->user_agent }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-sm text-slate-600">
                        Henuz cihaz bilgisi yok. Liste, bu hesaba giren cihazlar bildirimler sayfasinda durum gonderdikce dolacaktir.
                    </div>
                @endforelse
            </div>
        </div>
    </dialog>

    <dialog id="pwa-install-dialog" class="backdrop:bg-slate-900/50 w-[calc(100vw-1.5rem)] max-w-lg rounded-2xl border border-slate-200 p-0 max-sm:mx-auto">
        <form method="dialog" class="border-b border-slate-200 px-4 py-4 sm:px-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 data-pwa-install-dialog-title class="text-lg font-semibold text-slate-900">Kurulum Yardimi</h3>
                    <p class="mt-1 text-sm text-slate-500">Cihaziniza uygun kurulum adimlari burada gorunur.</p>
                </div>
                <button type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Kapat
                </button>
            </div>
        </form>

        <div data-pwa-install-dialog-body class="px-4 py-5 sm:px-6"></div>
    </dialog>
    <script>
        document.querySelectorAll('form[action="{{ route('push.send') }}"]').forEach((form) => {
            const audienceSelect = form.querySelector('[data-audience-select]');
            const roleWrap = form.querySelector('[data-role-target-wrap]');
            const usersWrap = form.querySelector('[data-users-target-wrap]');
            if (!audienceSelect || !roleWrap || !usersWrap) return;
            const sync = () => {
                const v = String(audienceSelect.value || '');
                roleWrap.classList.toggle('hidden', v !== 'role');
                usersWrap.classList.toggle('hidden', v !== 'users');
            };
            audienceSelect.addEventListener('change', sync);
            sync();
        });
    </script>
</x-app-layout>

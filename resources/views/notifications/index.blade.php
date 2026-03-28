<x-app-layout>
    <x-slot name="header">Bildirimler</x-slot>

    <div class="space-y-6">
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

        <section class="grid gap-6 lg:grid-cols-[minmax(0,1.3fr)_minmax(320px,0.9fr)]">
            <article class="lms-panel">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">PWA ve Push Bildirimleri</h3>
                        <p class="mt-1 text-sm text-slate-600">Uygulamayi telefona ekleyip bu cihaz icin anlik bildirim alabilirsiniz.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" data-pwa-install class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                            Uygulamayi Kur
                        </button>
                        <button type="button" data-push-enable class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Bildirimleri Ac
                        </button>
                        <button type="button" data-push-disable class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Kapat
                        </button>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-[220px_minmax(0,1fr)]">
                    <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                        <div class="text-xs uppercase tracking-wide text-slate-400">Cihaz Durumu</div>
                        <div class="mt-1 font-semibold" data-push-count data-count="{{ $pushSubscriptionCount }}">{{ $pushSubscriptionCount > 0 ? $pushSubscriptionCount.' cihaz bagli' : 'Bagli cihaz yok' }}</div>
                    </div>
                    <div data-push-status class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600">
                        Bildirimleri acarak odev, gorusme ve sistem duyurularini anlik alabilirsiniz.
                    </div>
                </div>

                <div data-pwa-install-status class="mt-3 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600">
                    Uygulamayi telefona eklemek icin kurulum butonunu kullanin.
                </div>
            </article>

            @if(auth()->user()?->hasRole('admin'))
                <article class="lms-panel">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="lms-panel-title">Push Bildirim Gonder</h3>
                        <span class="text-xs text-slate-500">PWA uzerinden anlik bildirim</span>
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
                            <label class="mb-1 block text-sm font-medium text-slate-700">Hedef</label>
                            <select name="audience" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                <option value="self">Sadece benim cihazlarim</option>
                                <option value="all">Tum kullanicilarin cihazlari</option>
                            </select>
                        </div>
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                            Bildirim Gonder
                        </button>
                    </form>
                </article>
            @endif
        </section>

        <section class="lms-panel">
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

            <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 p-4">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h4 class="text-sm font-semibold text-rose-800">Hatali Bildirimler</h4>
                        <p class="text-xs text-rose-600">Son 20 hatali veya kismi sonuc kaydi</p>
                    </div>
                </div>
                <div class="space-y-3">
                    @forelse($failedLogs as $log)
                        <div class="rounded-xl border border-rose-200 bg-white px-3 py-3">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0">
                                    <div class="font-medium text-slate-800">{{ $log->title }}</div>
                                    <div class="text-xs text-slate-500">{{ optional($log->sent_at)->format('d.m.Y H:i') }} | {{ $log->status }}</div>
                                    <div class="mt-1 text-sm text-slate-600">{{ $log->error_message ?: 'Hata detayi yok.' }}</div>
                                </div>
                                @if(auth()->user()?->hasRole('admin'))
                                    <form method="POST" action="{{ route('notifications.resend', $log) }}">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-700">
                                            Tekrar Gonder
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-sm text-slate-600">Hatali bildirim kaydi yok.</div>
                    @endforelse
                </div>
            </div>

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
                                    @if(auth()->user()?->hasRole('admin'))
                                        <form method="POST" action="{{ route('notifications.resend', $log) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">
                                                Tekrar Gonder
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">Bildirim kaydi bulunamadi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>

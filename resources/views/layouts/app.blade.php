<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f172a">

    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/logo.png') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media (max-width: 1024px) {
            .mobile-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        }
        @media (max-width: 640px) {
            .mobile-progress-box {
                right: 12px !important;
                left: 12px !important;
                bottom: 12px !important;
                width: auto !important;
                max-width: none !important;
            }
        }
    </style>
</head>
<body class="font-sans antialiased overflow-hidden" data-app-base-url="{{ url('/') }}">
    @php
        $headerNotifications = collect();
        $headerNotificationCount = 0;
        $readNotificationIds = collect();

        if (auth()->check() && \Illuminate\Support\Facades\Schema::hasTable('notification_logs')) {
            $currentUser = auth()->user();

            $headerNotifications = \App\Models\NotificationLog::query()
                ->visibleToUser($currentUser)
                ->whereIn('status', ['sent', 'partial'])
                ->latest('sent_at')
                ->limit(8)
                ->get();

            if (\Illuminate\Support\Facades\Schema::hasTable('notification_log_reads') && $headerNotifications->isNotEmpty()) {
                $readNotificationIds = \App\Models\NotificationLogRead::query()
                    ->where('user_id', $currentUser->id)
                    ->whereIn('notification_log_id', $headerNotifications->pluck('id'))
                    ->pluck('notification_log_id');

                $headerNotificationCount = \App\Models\NotificationLog::query()
                    ->visibleToUser($currentUser)
                    ->whereIn('status', ['sent', 'partial'])
                    ->whereDoesntHave('reads', function ($query) use ($currentUser) {
                        $query->where('user_id', $currentUser->id);
                    })
                    ->count();
            } else {
                $headerNotificationCount = $headerNotifications->count();
            }
        }
    @endphp

    <div x-data="{ sidebarOpen: false }" class="h-screen lms-bg text-slate-900 overflow-hidden">
        <div class="flex h-screen min-w-0 overflow-hidden">
            <aside class="hidden lg:flex w-72 flex-col border-r border-slate-200 bg-white/90 backdrop-blur">
                @include('layouts.navigation')
            </aside>

            <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
                <header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-slate-200 flex items-center justify-between px-3 sm:px-6 lg:px-8" style="height:64px;min-height:64px;max-height:64px;flex:0 0 64px;">
                    <div class="flex items-center gap-3 min-w-0 flex-1 overflow-hidden">
                        <button
                            @click="sidebarOpen = true"
                            class="lg:hidden inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white h-12 w-12 text-slate-700 shadow-sm hover:bg-slate-50 transition"
                            aria-label="Menüyü Aç"
                        >
                            <span aria-hidden="true" style="display:inline-block;line-height:0;">
                                <span style="display:block;width:22px;height:2px;background:#0f172a;border-radius:9999px;"></span>
                                <span style="display:block;width:22px;height:2px;background:#0f172a;border-radius:9999px;margin-top:5px;"></span>
                                <span style="display:block;width:22px;height:2px;background:#0f172a;border-radius:9999px;margin-top:5px;"></span>
                            </span>
                        </button>
                        @isset($header)
                            <div class="lms-page-title text-slate-800 whitespace-nowrap overflow-hidden text-ellipsis min-w-0 [&_*]:whitespace-nowrap [&_*]:overflow-hidden [&_*]:text-ellipsis">{{ $header }}</div>
                        @else
                            <div class="lms-page-title text-slate-800 whitespace-nowrap overflow-hidden text-ellipsis min-w-0">LMS Panel</div>
                        @endisset
                    </div>

                    <div class="flex items-center gap-2 shrink-0 ml-3">
                        <div class="relative" x-data="{ notificationMenuOpen: false }">
                            <button
                                type="button"
                                @click="notificationMenuOpen = !notificationMenuOpen"
                                class="relative inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-700 shadow-sm hover:bg-slate-50"
                                aria-label="Bildirimler"
                            >
                                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17H9.143m10.286 0H20a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2h.571m14.286 0V11a6.857 6.857 0 1 0-13.714 0v6m13.714 0H5.143" />
                                </svg>
                                @if($headerNotificationCount > 0)
                                    <span class="absolute -right-1 -top-1 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-rose-600 px-1.5 py-0.5 text-[10px] font-semibold text-white">
                                        {{ $headerNotificationCount > 9 ? '9+' : $headerNotificationCount }}
                                    </span>
                                @endif
                            </button>

                            <div
                                x-show="notificationMenuOpen"
                                @click.outside="notificationMenuOpen = false"
                                style="display:none;"
                                class="absolute right-0 mt-2 w-[22rem] max-w-[calc(100vw-1.5rem)] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl z-50"
                            >
                                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                                    <div>
                                        <div class="text-sm font-semibold text-slate-900">Bildirimler</div>
                                        <div class="text-xs text-slate-500">Son gonderilen bildirimler</div>
                                    </div>
                                    <a href="{{ route('notifications.index') }}" class="text-xs font-semibold text-slate-700 hover:text-slate-900">
                                        Tumu
                                    </a>
                                </div>

                                <div class="max-h-[26rem] overflow-y-auto">
                                    @forelse($headerNotifications as $notification)
                                        @php
                                            $isRead = $readNotificationIds->contains($notification->id);
                                        @endphp
                                        <a
                                            href="{{ $notification->url ?: route('notifications.index') }}"
                                            data-notification-read-url="{{ route('notifications.read', $notification) }}"
                                            class="block border-b border-slate-100 px-4 py-3 hover:bg-slate-50 {{ $isRead ? 'bg-white' : 'bg-sky-50/70' }}"
                                        >
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <div class="truncate text-sm font-semibold {{ $isRead ? 'text-slate-900' : 'text-sky-950' }}">{{ $notification->title }}</div>
                                                    <p class="mt-1 overflow-hidden text-ellipsis text-xs text-slate-600">{{ \Illuminate\Support\Str::limit($notification->body, 110) }}</p>
                                                </div>
                                                <div class="flex flex-col items-end gap-2">
                                                    <span class="rounded-full px-2 py-1 text-[10px] font-semibold {{ $notification->status === 'partial' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                                                        {{ $notification->status }}
                                                    </span>
                                                    @unless($isRead)
                                                        <span class="rounded-full bg-sky-600 px-2 py-1 text-[10px] font-semibold text-white">
                                                            Yeni
                                                        </span>
                                                    @endunless
                                                </div>
                                            </div>
                                            <div class="mt-2 text-[11px] text-slate-500">
                                                {{ optional($notification->sent_at)->format('d.m.Y H:i') }}
                                            </div>
                                        </a>
                                    @empty
                                        <div class="px-4 py-6 text-sm text-slate-500">
                                            Gosterilecek bildirim yok.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="relative" x-data="{ userMenuOpen: false }">
                            <button type="button"
                                    @click="userMenuOpen = !userMenuOpen"
                                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700">
                                <span>{{ auth()->user()->name }}</span>
                                <span class="text-xs">▼</span>
                            </button>
                            <div x-show="userMenuOpen"
                                 @click.outside="userMenuOpen = false"
                                 style="display:none;"
                                 class="absolute right-0 mt-2 w-44 rounded-lg border border-slate-200 bg-white shadow-lg z-50">
                                <a href="{{ route('profile.edit') }}" class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Profil</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="w-full text-left px-3 py-2 text-sm text-rose-600 hover:bg-rose-50">Çıkış Yap</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </header>

                <main class="flex-1 px-3 sm:px-5 lg:px-8 pt-1 pb-4 sm:pb-5 lg:pb-8 overflow-y-auto overflow-x-hidden">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <div x-show="sidebarOpen" class="fixed inset-0 z-40 lg:hidden" style="display:none;" @keydown.escape.window="sidebarOpen = false">
            <div class="absolute inset-0 bg-black/40" @click="sidebarOpen = false"></div>
            <aside
                x-show="sidebarOpen"
                x-transition:enter="transform transition ease-out duration-[1000ms]"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in duration-[1000ms]"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
                class="absolute left-0 top-0 h-full w-[86vw] max-w-72 bg-white border-r border-slate-200"
                style="display:none;"
            >
                @include('layouts.navigation')
            </aside>
        </div>
    </div>
</body>
</html>

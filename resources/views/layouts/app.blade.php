<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f172a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Ozelsin">

    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <script>
        (() => {
            const storedTheme = window.localStorage.getItem('lms_theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if ((storedTheme || (prefersDark ? 'dark' : 'light')) === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --app-safe-top: env(safe-area-inset-top, 0px);
            --app-header-height: calc(64px + var(--app-safe-top));
        }
        @media (max-width: 1024px) {
            .mobile-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
            body { overflow-y: auto !important; }
            .lms-shell,
            .lms-shell-inner,
            .lms-shell-main {
                height: auto !important;
                min-height: 100vh !important;
                overflow: visible !important;
            }
            .lms-main-content {
                overflow: visible !important;
                max-height: none !important;
            }
        }
        @media (max-width: 640px) {
            .mobile-progress-box {
                right: 12px !important;
                left: 12px !important;
                bottom: 12px !important;
                width: auto !important;
                max-width: none !important;
            }
            .lms-header-actions {
                gap: 0.35rem !important;
                margin-left: 0.5rem !important;
            }
            .lms-theme-toggle {
                width: 2.35rem !important;
                height: 2.35rem !important;
                min-height: 2.35rem !important;
                flex: 0 0 auto !important;
            }
            .lms-user-name {
                display: none !important;
            }
        }
    </style>
</head>
<body
    class="font-sans antialiased overflow-hidden"
    data-app-base-url="{{ url('/') }}"
    data-auth-user-id="{{ auth()->id() }}"
    data-push-prompt-on-login="{{ session('showPushPrompt') ? '1' : '0' }}"
>
    @php
        $headerNotifications = collect();
        $headerNotificationCount = 0;
        $readNotificationIds = collect();
        $headerPageTitle = 'Dashboard';

        if (request()->routeIs('users.*')) {
            $headerPageTitle = 'Kullanıcı Yönetimi';
        } elseif (request()->routeIs('assignments.*')) {
            $headerPageTitle = 'Ödev Yönetimi';
        } elseif (request()->routeIs('attendance.*')) {
            $headerPageTitle = 'Yoklama Modülü';
        } elseif (request()->routeIs('timetables.*')) {
            $headerPageTitle = 'Ders Programı';
        } elseif (request()->routeIs('meetings.*')) {
            $headerPageTitle = 'Görüşmeler';
        } elseif (request()->routeIs('reports.*')) {
            $headerPageTitle = 'Raporlama';
        } elseif (request()->routeIs('books.*')) {
            $headerPageTitle = 'Kitap Yönetimi';
        } elseif (request()->routeIs('whatsapp.*')) {
            $headerPageTitle = 'Whatsapp Modülü';
        } elseif (request()->routeIs('lessons.*')) {
            $headerPageTitle = 'Ders Ekleme Modülü';
        } elseif (request()->routeIs('role-permissions.*')) {
            $headerPageTitle = 'Rol ve Modül Yetkileri';
        } elseif (request()->routeIs('notifications.*') || request()->routeIs('push.*')) {
            $headerPageTitle = 'Bildirimler';
        }

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

    <div x-data="{ sidebarOpen: false }" class="h-screen lms-bg text-slate-900 overflow-hidden lms-shell">
        <div class="flex h-screen min-w-0 overflow-hidden lms-shell-inner">
            <aside class="hidden lg:flex w-72 flex-col border-r border-slate-200 bg-white/90 backdrop-blur">
                <div class="lms-sidebar-brand" style="margin-top:calc(var(--app-header-height) * 0.18);">
                    <a href="{{ url('dashboard') }}" class="flex items-center gap-3 min-w-0">
                        <img src="{{ asset('assets/logo.png') }}" alt="LMS Logo" class="h-10 w-10 rounded-lg bg-white p-1 border border-slate-200 object-contain">
                        <div class="lms-page-title text-slate-800 whitespace-nowrap overflow-hidden text-ellipsis min-w-0 font-semibold">Özelsin Bilişim Sistemleri</div>
                    </a>
                </div>
                @include('layouts.navigation')
            </aside>

            <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden lms-shell-main">
                <header class="fixed top-0 left-0 lg:left-72 right-0 z-30 lms-topbar bg-white/90 backdrop-blur border-b border-slate-200 flex items-center justify-between px-3 sm:px-6 lg:px-8" style="height:var(--app-header-height);min-height:var(--app-header-height);padding-top:var(--app-safe-top);box-sizing:border-box;">
                    <div class="flex items-center gap-3 min-w-0 flex-1 overflow-hidden">
                        <button
                            @click="sidebarOpen = true"
                            class="lg:hidden inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white h-12 w-12 text-slate-700 shadow-sm hover:bg-slate-50 transition"
                            aria-label="Menüyü Aç"
                        >
                            <span class="lms-mobile-menu-icon" aria-hidden="true">
                                <span></span>
                                <span></span>
                                <span></span>
                            </span>
                        </button>
                        <h1 class="hidden lg:block text-lg font-semibold text-slate-800 truncate">{{ $headerPageTitle }}</h1>
                    </div>

                    <div class="flex items-center gap-2 shrink-0 ml-3 lms-header-actions">
                        <button
                            type="button"
                            class="lms-theme-toggle"
                            data-theme-toggle
                            aria-label="Tema degistir"
                            aria-pressed="false"
                        >
                            <span class="lms-theme-toggle-icon" data-theme-toggle-icon>
                                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor" aria-hidden="true">
                                    <path d="M12 3a1 1 0 0 1 1 1v1.2a1 1 0 1 1-2 0V4a1 1 0 0 1 1-1Zm0 14.8a1 1 0 0 1 1 1V20a1 1 0 1 1-2 0v-1.2a1 1 0 0 1 1-1Zm8-5.8a1 1 0 0 1 1 1 1 1 0 0 1-1 1h-1.2a1 1 0 1 1 0-2H20ZM5.2 12a1 1 0 1 1 0 2H4a1 1 0 1 1 0-2h1.2Zm11.75-5.95a1 1 0 0 1 1.41 1.41l-.85.85a1 1 0 0 1-1.41-1.41l.85-.85Zm-9.1 9.1a1 1 0 0 1 1.41 1.41l-.85.85A1 1 0 0 1 7 15.99l.85-.84Zm9.1 2.26a1 1 0 0 1-1.41 0l-.85-.85a1 1 0 1 1 1.41-1.41l.85.85a1 1 0 0 1 0 1.41ZM8.7 8.7a1 1 0 0 1-1.41 0l-.85-.85a1 1 0 1 1 1.41-1.41l.85.85A1 1 0 0 1 8.7 8.7ZM12 8a4 4 0 1 1 0 8 4 4 0 0 1 0-8Z"/>
                                </svg>
                            </span>
                            <span class="lms-theme-toggle-label" data-theme-toggle-label>Light</span>
                        </button>

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
                                class="absolute right-0 mt-2 w-[22rem] max-w-[calc(100vw-1.5rem)] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl z-50 lms-header-notification-menu"
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
                                <span class="lms-user-name">{{ auth()->user()->name }}</span>
                                <span class="text-xs">&#9662;</span>
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

                <main class="flex-1 px-3 sm:px-5 lg:px-8 pt-0 pb-32 sm:pb-12 lg:pb-8 overflow-y-auto overflow-x-hidden lms-main-content" style="margin-top:var(--app-header-height);">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <div x-show="sidebarOpen" class="fixed inset-0 z-40 lg:hidden" style="display:none;" @keydown.escape.window="sidebarOpen = false">
            <div class="absolute inset-0 bg-black/40" @click="sidebarOpen = false"></div>
            <aside
                x-show="sidebarOpen"
                x-transition:enter="transform transition ease-out duration-[120ms]"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in duration-[100ms]"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
                class="absolute left-0 w-[86vw] max-w-72 bg-white border-r border-slate-200"
                style="display:none; top: 0; height: 100%;"
            >
                @include('layouts.navigation')
            </aside>
        </div>

        <div
            data-push-prompt-modal
            class="fixed inset-0 z-[70] hidden items-center justify-center bg-slate-950/35 px-4 backdrop-blur-[2px]"
            aria-hidden="true"
        >
            <div class="relative w-full max-w-xl rounded-[1.75rem] bg-white px-5 py-5 shadow-2xl sm:px-8 sm:py-7">
                <button
                    type="button"
                    data-push-prompt-close
                    class="absolute right-4 top-4 inline-flex h-10 w-10 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                    aria-label="Kapat"
                >
                    <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>

                <div class="flex items-center gap-3 pr-10">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-sky-100 text-sky-600">
                        <svg viewBox="0 0 24 24" class="h-6 w-6" fill="currentColor" aria-hidden="true">
                            <path d="M12 2a6 6 0 0 0-6 6v3.764l-1.447 2.894A1 1 0 0 0 5.447 16h13.106a1 1 0 0 0 .894-1.447L18 11.764V8a6 6 0 0 0-6-6Zm0 20a3 3 0 0 0 2.816-2H9.184A3 3 0 0 0 12 22Z" />
                        </svg>
                    </div>
                    <div class="text-[2rem] font-semibold tracking-tight text-slate-800 sm:text-[2.15rem]">Push Bildirimleri</div>
                </div>

                <div class="mt-6 max-w-xl space-y-4">
                    <p class="text-base leading-8 text-slate-700 sm:text-[1.7rem] sm:leading-[2.7rem]">
                        Onay talepleri, yönetici mesajları ve sistem uyarılarını anlık almak için tarayıcı bildirimi izni verebilirsiniz.
                    </p>
                    <p class="text-sm leading-7 text-slate-500 sm:text-[1.35rem] sm:leading-[2.25rem]">
                        İzin verirseniz bu cihaz ve tarayıcı için abonelik oluşturulur. Daha sonra kullanıcı menüsünden test bildirimi de gönderebilirsiniz.
                    </p>
                    <div
                        data-push-prompt-status
                        class="hidden rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600"
                    ></div>
                </div>

                <label class="mt-7 inline-flex items-center gap-3 text-base text-slate-700 sm:text-[1.35rem]">
                    <input
                        type="checkbox"
                        data-push-prompt-never
                        class="h-5 w-5 rounded-md border-slate-300 text-sky-600 focus:ring-sky-500"
                    >
                    <span>Bir daha hatırlatma</span>
                </label>

                <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <button
                        type="button"
                        data-push-prompt-later
                        class="inline-flex items-center justify-center rounded-2xl bg-slate-100 px-5 py-2.5 text-base font-medium text-slate-700 transition hover:bg-slate-200"
                    >
                        Şimdi Değil
                    </button>
                    <button
                        type="button"
                        data-push-prompt-allow
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-2.5 text-base font-semibold text-white transition hover:bg-blue-700"
                    >
                        <svg viewBox="0 0 24 24" class="h-4.5 w-4.5" fill="currentColor" aria-hidden="true">
                            <path d="M12 2a6 6 0 0 0-6 6v3.764l-1.447 2.894A1 1 0 0 0 5.447 16h13.106a1 1 0 0 0 .894-1.447L18 11.764V8a6 6 0 0 0-6-6Zm0 20a3 3 0 0 0 2.816-2H9.184A3 3 0 0 0 12 22Z" />
                        </svg>
                        İzin Ver
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

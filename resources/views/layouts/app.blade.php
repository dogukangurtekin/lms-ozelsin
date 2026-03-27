<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/logo.png') }}">
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
<body class="font-sans antialiased overflow-hidden">
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

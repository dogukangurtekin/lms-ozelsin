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
<body class="font-sans antialiased">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen lms-bg text-slate-900">
        <div class="flex min-h-screen">
            <aside class="hidden lg:flex w-72 flex-col border-r border-slate-200 bg-white/90 backdrop-blur">
                @include('layouts.navigation')
            </aside>

            <div class="flex-1 flex flex-col">
                <header class="h-16 bg-white/85 backdrop-blur border-b border-slate-200 flex items-center justify-between px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3">
                        <button @click="sidebarOpen = true" class="lg:hidden rounded-md border border-slate-300 px-2 py-1 text-sm">Menu</button>
                        @isset($header)
                            <div class="font-semibold text-slate-800">{{ $header }}</div>
                        @else
                            <div class="font-semibold text-slate-800">LMS Panel</div>
                        @endisset
                    </div>
                    <div class="text-sm text-slate-500">{{ auth()->user()->name }}</div>
                </header>

                <main class="p-3 sm:p-5 lg:p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <div x-show="sidebarOpen" class="fixed inset-0 z-40 lg:hidden" style="display:none;">
            <div class="absolute inset-0 bg-black/40" @click="sidebarOpen = false"></div>
            <aside class="absolute left-0 top-0 h-full w-[86vw] max-w-72 bg-white border-r border-slate-200">
                @include('layouts.navigation')
            </aside>
        </div>
    </div>
</body>
</html>

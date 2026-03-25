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
</head>
<body class="font-sans antialiased lms-bg-auth">
    <div class="min-h-screen grid lg:grid-cols-2">
        <section class="hidden lg:flex flex-col justify-between p-12 text-white lms-auth-side">
            <div class="flex items-center gap-3">
                <img src="{{ asset('assets/logo.png') }}" alt="LMS Logo" class="h-12 w-12 rounded-xl bg-white p-1 object-contain">
                <div>
                    <p class="text-lg font-semibold">OzelSin LMS</p>
                    <p class="text-xs text-white/80">Akilli Egitim Yonetim Sistemi</p>
                </div>
            </div>
            <div>
                <h1 class="text-3xl font-bold leading-tight">Tek platformda odev, kitap, gorusme ve veli iletisimi.</h1>
                <p class="mt-4 text-sm text-white/80">Okulunuzun tum sureclerini guvenli ve olceklenebilir bir yapiyla yonetin.</p>
            </div>
            <p class="text-xs text-white/70">© {{ date('Y') }} OzelSin LMS</p>
        </section>

        <section class="flex items-center justify-center p-6 sm:p-8">
            <div class="w-full max-w-md">
                <div class="lg:hidden mb-6 flex justify-center">
                    <img src="{{ asset('assets/logo.png') }}" alt="LMS Logo" class="h-14 w-14 rounded-xl bg-white p-1 object-contain shadow">
                </div>
                <div class="lms-auth-card">
                    {{ $slot }}
                </div>
            </div>
        </section>
    </div>
</body>
</html>

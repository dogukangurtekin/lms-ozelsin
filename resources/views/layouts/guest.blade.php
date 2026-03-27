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
        <section class="hidden lg:flex flex-col justify-between p-12 text-white lms-auth-side" style="background: linear-gradient(160deg, #2f4f75 0%, #2aa39a 80%);">
            <div class="flex-1 flex flex-col items-center justify-center text-center gap-4">
                <img src="{{ asset('assets/logo.png') }}" alt="Özelsin Eğitim Platformu Logosu" class="object-contain mx-auto" style="width:200px;height:200px;">
                <div>
                    <p class="text-3xl font-bold tracking-tight">Özelsin Eğitim Platformu</p>
                    <p class="text-sm text-white/85 mt-1">Akıllı Eğitim Yönetim Sistemi</p>
                </div>
            </div>
            <div class="text-center">
                <h1 class="text-lg font-semibold leading-tight text-white/95">Tek platformda ödev, kitap, görüşme ve veli iletişimi.</h1>
                <p class="mt-2 text-sm text-white/80">Okulunuzun tüm süreçlerini güvenli ve ölçeklenebilir bir yapıyla yönetin.</p>
            </div>
            <p class="text-xs text-white/70">© {{ date('Y') }} Özelsin Eğitim Platformu</p>
        </section>

        <section class="flex items-center justify-center p-6 sm:p-8">
            <div class="w-full max-w-md">
                <div class="lg:hidden mb-6 flex flex-col items-center gap-2">
                    <img src="{{ asset('assets/logo.png') }}" alt="Özelsin Eğitim Platformu Logosu" class="object-contain mx-auto" style="width:200px;height:200px;">
                    <p class="text-base font-semibold text-slate-800 text-center">Özelsin Eğitim Platformu</p>
                </div>
                <div class="lms-auth-card">
                    {{ $slot }}
                </div>
            </div>
        </section>
    </div>
</body>
</html>

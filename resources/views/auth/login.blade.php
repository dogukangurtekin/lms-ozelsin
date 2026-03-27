<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-2xl font-bold text-slate-900">Giris Yap</h2>
        <p class="text-sm text-slate-500 mt-1">Hesabiniza eriserek LMS paneline devam edin.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">E-posta</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="mt-1 block w-full rounded-lg border-slate-300 focus:border-slate-800 focus:ring-slate-800">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Sifre</label>
            <input id="password" type="password" name="password" required autocomplete="current-password" class="mt-1 block w-full rounded-lg border-slate-300 focus:border-slate-800 focus:ring-slate-800">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between">
            <label class="inline-flex items-center text-sm text-slate-600">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-slate-900 focus:ring-slate-800">
                <span class="ms-2">Beni hatirla</span>
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm text-slate-600 hover:text-slate-900">Sifremi unuttum</a>
            @endif
        </div>

        <button type="submit" class="w-full rounded-lg bg-slate-900 text-white py-2.5 font-medium hover:bg-slate-800">Giris Yap</button>

    </form>
</x-guest-layout>

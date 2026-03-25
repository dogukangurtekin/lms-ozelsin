<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-2xl font-bold text-slate-900">Kayit Ol</h2>
        <p class="text-sm text-slate-500 mt-1">Yeni hesap olusturarak sisteme katilin.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-slate-700">Ad Soyad</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" class="mt-1 block w-full rounded-lg border-slate-300 focus:border-slate-800 focus:ring-slate-800">
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">E-posta</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" class="mt-1 block w-full rounded-lg border-slate-300 focus:border-slate-800 focus:ring-slate-800">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Sifre</label>
            <input id="password" type="password" name="password" required autocomplete="new-password" class="mt-1 block w-full rounded-lg border-slate-300 focus:border-slate-800 focus:ring-slate-800">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Sifre Tekrari</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="mt-1 block w-full rounded-lg border-slate-300 focus:border-slate-800 focus:ring-slate-800">
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <button type="submit" class="w-full rounded-lg bg-slate-900 text-white py-2.5 font-medium hover:bg-slate-800">Kayit Ol</button>

        <p class="text-sm text-center text-slate-600">
            Zaten hesabin var mi?
            <a href="{{ route('login') }}" class="font-semibold text-slate-900">Giris Yap</a>
        </p>
    </form>
</x-guest-layout>

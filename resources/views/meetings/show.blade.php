<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gorusme Detayi</h2>
            <a href="{{ route('meetings.index') }}" class="text-sm text-slate-600">Listeye Don</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="rounded-lg bg-slate-50 p-3"><span class="text-slate-500">Tarih:</span> <strong>{{ optional($meeting->meeting_at)->format('d.m.Y H:i') }}</strong></div>
                    <div class="rounded-lg bg-slate-50 p-3"><span class="text-slate-500">Durum:</span> <strong>{{ $meeting->status }}</strong></div>
                    <div class="rounded-lg bg-slate-50 p-3"><span class="text-slate-500">Ogretmen:</span> <strong>{{ $meeting->teacher?->name }}</strong></div>
                    <div class="rounded-lg bg-slate-50 p-3"><span class="text-slate-500">Ogrenci:</span> <strong>{{ $meeting->student?->name ?? '-' }}</strong></div>
                    <div class="rounded-lg bg-slate-50 p-3 md:col-span-2"><span class="text-slate-500">Veli:</span> <strong>{{ $meeting->parentUser?->name ?? '-' }}</strong></div>
                </div>

                <div>
                    <h3 class="font-semibold mb-2">Gorusme Notlari</h3>
                    <div class="rounded-lg border border-slate-200 p-4 text-slate-700">{{ $meeting->notes ?: 'Not girilmemis.' }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-2">
            <h2 class="font-semibold text-xl">Gorusmeler</h2>
            @if(auth()->user()->hasRole(['admin','teacher']))
                <a href="{{ route('meetings.create') }}" class="rounded-lg bg-blue-600 text-white px-4 py-2 text-sm font-semibold">Yeni Randevu</a>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('status'))
                <div class="mb-3 rounded-lg bg-green-100 text-green-800 p-2">{{ session('status') }}</div>
            @endif

            <div class="bg-white rounded shadow p-4 mobile-table-wrap">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="p-2">Tarih</th>
                            <th class="p-2">Ogretmen</th>
                            <th class="p-2">Ogrenci</th>
                            <th class="p-2">Veli</th>
                            <th class="p-2">Durum</th>
                            <th class="p-2">Islem</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($meetings as $meeting)
                        <tr class="border-b">
                            <td class="p-2">{{ optional($meeting->meeting_at)->format('d.m.Y H:i') }}</td>
                            <td class="p-2">{{ $meeting->teacher?->name ?? '-' }}</td>
                            <td class="p-2">{{ $meeting->student?->name ?? '-' }}</td>
                            <td class="p-2">{{ $meeting->parentUser?->name ?? '-' }}</td>
                            <td class="p-2">{{ $meeting->status ?? 'scheduled' }}</td>
                            <td class="p-2 min-w-[220px]">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('meetings.show', $meeting) }}" class="inline-flex rounded px-3 py-1 text-xs font-semibold" style="background:#059669;color:#ffffff;border:1px solid #047857;">Detay</a>
                                    @if(auth()->user()->hasRole(['admin','teacher']))
                                        <a href="{{ route('meetings.edit', $meeting) }}" class="inline-flex rounded px-3 py-1 text-xs font-semibold" style="background:#4f46e5;color:#ffffff;border:1px solid #4338ca;">Duzenle</a>
                                        <form method="POST" action="{{ route('meetings.destroy', $meeting) }}" onsubmit="return confirm('Kayit silinsin mi?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="inline-flex rounded px-3 py-1 text-xs font-semibold" style="background:#e11d48;color:#ffffff;border:1px solid #be123c;">Sil</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="p-2" colspan="6">Kayit yok</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="mt-4">{{ $meetings->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">Görüşmeler</x-slot>

    @php
        $statusLabels = [
            'scheduled' => 'Planlandı',
            'completed' => 'Tamamlandı',
            'cancelled' => 'İptal Edildi',
        ];
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(auth()->user()->hasRole(['admin','teacher']))
                <div class="mb-4">
                    <a href="{{ route('meetings.create') }}" class="inline-flex rounded-lg bg-blue-600 text-white px-4 py-2 text-sm font-semibold">Yeni Görüşme</a>
                </div>
            @endif

            @if(session('status'))
                <div class="mb-3 rounded-lg bg-green-100 text-green-800 p-2">{{ session('status') }}</div>
            @endif

            <div class="bg-white rounded shadow p-4">
                <div class="space-y-3 md:hidden">
                    @forelse($meetings as $meeting)
                        <article class="rounded-xl border border-slate-200 p-3">
                            <p class="text-xs text-slate-500">{{ optional($meeting->meeting_at)->format('d.m.Y H:i') }}</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800">{{ $meeting->student?->name ?? '-' }}</p>
                            <p class="text-xs text-slate-600">Öğretmen: {{ $meeting->teacher?->name ?? '-' }}</p>
                            <p class="text-xs text-slate-600">Veli: {{ $meeting->parentUser?->name ?? '-' }}</p>
                            <p class="text-xs text-slate-600">Durum: {{ $statusLabels[$meeting->status] ?? $meeting->status }}</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <a href="{{ route('meetings.show', $meeting) }}" class="lms-action-btn view">Detay</a>
                                @if(auth()->user()->hasRole(['admin','teacher']))
                                    <a href="{{ route('meetings.edit', $meeting) }}" class="lms-action-btn edit">Düzenle</a>
                                    <form method="POST" action="{{ route('meetings.destroy', $meeting) }}" onsubmit="return confirm('Kayıt silinsin mi?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="lms-action-btn delete">Sil</button>
                                    </form>
                                    <form method="POST" action="{{ route('meetings.status.update', $meeting) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="status" value="completed">
                                        <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-emerald-300 text-emerald-700 hover:bg-emerald-50" title="Görüşme yapıldı">
                                            &#10003;
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('meetings.status.update', $meeting) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="status" value="cancelled">
                                        <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-rose-300 text-rose-700 hover:bg-rose-50" title="Görüşme yapılmadı">
                                            &#10005;
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="text-sm text-slate-500">Kayıt yok</p>
                    @endforelse
                </div>

                <div class="hidden md:block mobile-table-wrap">
                    <table class="min-w-[760px] w-full text-sm">
                        <thead>
                            <tr class="border-b text-left">
                                <th class="p-2">Tarih</th>
                                <th class="p-2">Öğretmen</th>
                                <th class="p-2">Öğrenci</th>
                                <th class="p-2">Veli</th>
                                <th class="p-2">Durum</th>
                                <th class="p-2">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($meetings as $meeting)
                            <tr class="border-b">
                                <td class="p-2">{{ optional($meeting->meeting_at)->format('d.m.Y H:i') }}</td>
                                <td class="p-2">{{ $meeting->teacher?->name ?? '-' }}</td>
                                <td class="p-2">{{ $meeting->student?->name ?? '-' }}</td>
                                <td class="p-2">{{ $meeting->parentUser?->name ?? '-' }}</td>
                                <td class="p-2">{{ $statusLabels[$meeting->status] ?? $meeting->status }}</td>
                                <td class="p-2 min-w-[250px]">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('meetings.show', $meeting) }}" class="lms-action-btn view">Detay</a>
                                        @if(auth()->user()->hasRole(['admin','teacher']))
                                            <a href="{{ route('meetings.edit', $meeting) }}" class="lms-action-btn edit">Düzenle</a>
                                            <form method="POST" action="{{ route('meetings.destroy', $meeting) }}" onsubmit="return confirm('Kayıt silinsin mi?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="lms-action-btn delete">Sil</button>
                                            </form>
                                            <form method="POST" action="{{ route('meetings.status.update', $meeting) }}">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status" value="completed">
                                                <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-emerald-300 text-emerald-700 hover:bg-emerald-50" title="Görüşme yapıldı">
                                                    &#10003;
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('meetings.status.update', $meeting) }}">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status" value="cancelled">
                                                <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-rose-300 text-rose-700 hover:bg-rose-50" title="Görüşme yapılmadı">
                                                    &#10005;
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="p-2" colspan="6">Kayıt yok</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $meetings->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>

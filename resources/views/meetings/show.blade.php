<x-app-layout>
    <x-slot name="header">Görüşme Detayı</x-slot>

    @php
        $statusLabels = [
            'scheduled' => 'Planlandı',
            'completed' => 'Tamamlandı',
            'cancelled' => 'İptal Edildi',
        ];
        $canWriteTeacherNote = auth()->user()->hasRole('admin') || (auth()->user()->hasRole('teacher') && $meeting->teacher_id === auth()->id());
    @endphp

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if(session('status'))
                <div class="rounded-lg bg-emerald-100 text-emerald-800 p-3">{{ session('status') }}</div>
            @endif

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <a href="{{ route('meetings.index') }}" class="text-sm text-slate-600">Listeye Dön</a>
                    @if(auth()->user()->hasRole(['admin','teacher']))
                        <a href="{{ route('meetings.edit', $meeting) }}" class="text-sm rounded-lg border border-slate-300 px-3 py-1.5 text-slate-700">Görüşmeyi Düzenle</a>
                    @endif
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div class="rounded-lg bg-slate-50 p-3"><span class="text-slate-500">Tarih:</span> <strong>{{ optional($meeting->meeting_at)->format('d.m.Y H:i') }}</strong></div>
                    <div class="rounded-lg bg-slate-50 p-3"><span class="text-slate-500">Durum:</span> <strong>{{ $statusLabels[$meeting->status] ?? $meeting->status }}</strong></div>
                    <div class="rounded-lg bg-slate-50 p-3"><span class="text-slate-500">Öğretmen:</span> <strong>{{ $meeting->teacher?->name }}</strong></div>
                    <div class="rounded-lg bg-slate-50 p-3"><span class="text-slate-500">Öğrenci:</span> <strong>{{ $meeting->student?->name ?? '-' }}</strong></div>
                    <div class="rounded-lg bg-slate-50 p-3 md:col-span-2"><span class="text-slate-500">Veli:</span> <strong>{{ $meeting->parentUser?->name ?? '-' }}</strong></div>
                </div>

                <div class="rounded-lg border border-slate-200 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold text-slate-800">Öğretmen Notu</h3>
                    </div>
                    <div class="text-slate-700 whitespace-pre-wrap">{{ $meeting->notes ?: 'Henüz not eklenmemiş.' }}</div>
                </div>

                @if($canWriteTeacherNote)
                    <div class="rounded-lg border border-slate-200 p-4">
                        <h3 class="font-semibold text-slate-800 mb-2">Sonradan Öğretmen Notu Ekle / Güncelle</h3>
                        <form method="POST" action="{{ route('meetings.teacher-note.update', $meeting) }}" class="space-y-3">
                            @csrf
                            @method('PUT')
                            <textarea name="notes" rows="4" class="w-full border rounded p-2" placeholder="Öğretmen notu yazın...">{{ old('notes', $meeting->notes) }}</textarea>
                            <button class="rounded-lg bg-blue-600 text-white px-4 py-2 text-sm font-semibold">Öğretmen Notunu Kaydet</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

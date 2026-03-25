<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">WhatsApp Modülü</h2></x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if(session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-emerald-700 text-sm">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-rose-700 text-sm">
                    <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-4">
                <h3 class="font-semibold text-slate-800">Filtreleme</h3>
                <form method="GET" action="{{ route('whatsapp.index') }}" class="mt-3 grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Sınıf</label>
                        <select name="class_id" class="w-full rounded-lg border-slate-300">
                            <option value="">Tüm sınıflar</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected($selectedClassId === $class->id)>{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm">Öğrencileri Listele</button>
                </form>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4">
                <h3 class="font-semibold text-slate-800">Veliye Rapor Gönderimi (PDF)</h3>
                <form method="POST" action="{{ route('whatsapp.send') }}" class="mt-3 space-y-4">
                    @csrf
                    <input type="hidden" name="class_id" value="{{ $selectedClassId ?: '' }}">

                    <div>
                        <label class="block text-sm text-slate-600 mb-2">Öğrenci Seçimi</label>
                        <div class="max-h-72 overflow-auto grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-2 rounded-xl border border-slate-200 p-3">
                            @forelse($students as $student)
                                <label class="flex items-start gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
                                    <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="mt-1 rounded border-slate-300">
                                    <span class="text-sm text-slate-700">
                                        <span class="font-semibold">{{ $student->user?->name }}</span>
                                        <span class="block text-xs text-slate-500">{{ $student->class?->name ?? '-' }} | No: {{ $student->student_number ?? '-' }}</span>
                                    </span>
                                </label>
                            @empty
                                <p class="text-sm text-slate-500">Filtreye uygun öğrenci bulunamadı.</p>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-600 mb-2">İletilecek Veri Alanları</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-2">
                            <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm"><input type="checkbox" name="report_fields[]" value="identity" checked> Kimlik</label>
                            <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm"><input type="checkbox" name="report_fields[]" value="attendance" checked> Yoklama</label>
                            <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm"><input type="checkbox" name="report_fields[]" value="assignments" checked> Ödev</label>
                            <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm"><input type="checkbox" name="report_fields[]" value="performance" checked> Performans</label>
                            <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm"><input type="checkbox" name="report_fields[]" value="meetings"> Görüşme</label>
                            <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm"><input type="checkbox" name="report_fields[]" value="status"> Durum</label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Mesaj Notu (Opsiyonel)</label>
                        <textarea name="message_note" rows="3" class="w-full rounded-lg border-slate-300" placeholder="Velilere iletilecek ek not"></textarea>
                    </div>

                    <button class="rounded-lg px-4 py-2 text-sm font-semibold" style="background:#16a34a;color:#fff;border:1px solid #15803d;">PDF Raporu Velilere Kuyruğa Gönder</button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>

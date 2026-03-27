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

            @if(auth()->user()->hasRole('admin'))
                <section class="rounded-2xl border border-slate-200 bg-white p-4">
                    <h3 class="font-semibold text-slate-800">Gönderim Numarası</h3>
                    <form method="POST" action="{{ route('whatsapp.settings.update') }}" class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                        @csrf
                        <div class="md:col-span-2">
                            <label class="block text-sm text-slate-600 mb-1">Gönderilecek WhatsApp Numarası</label>
                            @php
                                $senderDigits = preg_replace('/\D/', '', (string) ($senderPhone ?? '')) ?? '';
                                $senderLocal = str_starts_with($senderDigits, '90') ? substr($senderDigits, 2) : $senderDigits;
                            @endphp
                            <div class="flex">
                                <span class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-slate-50 px-3 text-slate-600">+90</span>
                                <input
                                    name="sender_phone_local"
                                    value="{{ old('sender_phone_local', $senderLocal) }}"
                                    class="w-full rounded-r-lg border-slate-300"
                                    placeholder="5xxxxxxxxx"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                >
                            </div>
                            <p class="mt-1 text-xs text-slate-500">Sadece numarayı girin. Sistem otomatik olarak +90 ekler.</p>
                        </div>
                        <div>
                            <button class="w-full rounded-lg px-4 py-2 text-sm font-semibold" style="background:#0f172a;color:#fff;border:1px solid #0f172a;">
                                Numarayı Kaydet
                            </button>
                        </div>
                    </form>
                </section>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-4">
                <h3 class="font-semibold text-slate-800">Filtreleme</h3>
                <form method="GET" action="{{ route('whatsapp.index') }}" class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3 items-end">
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Hedef Tipi</label>
                        <select name="target_type" onchange="this.form.submit()" class="w-full rounded-lg border-slate-300">
                            <option value="parent_of_students" @selected($selectedTargetType === 'parent_of_students')>Öğrenci Velileri (PDF)</option>
                            <option value="teachers" @selected($selectedTargetType === 'teachers')>Öğretmenler</option>
                            <option value="parents" @selected($selectedTargetType === 'parents')>Veliler</option>
                            <option value="all_active" @selected($selectedTargetType === 'all_active')>Tüm Aktif Kullanıcılar</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Sınıf</label>
                        <select name="class_id" onchange="this.form.submit()" class="w-full rounded-lg border-slate-300">
                            <option value="">Tüm sınıflar</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected($selectedClassId === $class->id)>{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4">
                <h3 class="font-semibold text-slate-800">
                    {{ $selectedTargetType === 'parent_of_students' ? 'Veliye Rapor Gönderimi (PDF)' : 'Toplu WhatsApp Gönderimi' }}
                </h3>
                <form method="POST" action="{{ route('whatsapp.send') }}" class="mt-3 space-y-4">
                    @csrf
                    <input type="hidden" name="target_type" value="{{ $selectedTargetType }}">
                    <input type="hidden" name="class_id" value="{{ $selectedClassId ?: '' }}">
                    <input type="hidden" name="section" value="{{ $selectedSection }}">

                    @if($selectedTargetType === 'parent_of_students')
                        <div>
                            <label class="block text-sm text-slate-600 mb-2">Öğrenci Seçimi (Boş bırakılırsa filtreye uyan tüm öğrenciler)</label>
                            <div class="max-h-72 overflow-auto grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-2 rounded-xl border border-slate-200 p-3">
                                @forelse($students as $student)
                                    <label class="flex items-start gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50 min-w-0">
                                        <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="mt-1 rounded border-slate-300">
                                        <span class="text-sm text-slate-700 min-w-0 break-words">
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
                            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-2">
                                <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm"><input type="checkbox" name="report_fields[]" value="identity" checked> Kimlik</label>
                                <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm"><input type="checkbox" name="report_fields[]" value="attendance" checked> Yoklama</label>
                                <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm"><input type="checkbox" name="report_fields[]" value="assignments" checked> Ödev</label>
                                <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm"><input type="checkbox" name="report_fields[]" value="performance" checked> Performans</label>
                                <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm"><input type="checkbox" name="report_fields[]" value="meetings"> Görüşme</label>
                                <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm"><input type="checkbox" name="report_fields[]" value="status"> Durum</label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm text-slate-600 mb-1">Mesaj Şablonu</label>
                            <textarea name="message_template" rows="4" class="w-full rounded-lg border-slate-300" placeholder="Özelsin Koleji Ortaokulu Bilgilendirme Sistemi.&#10;Değerli velim {veli_adi}, {ogrenci_adi} öğrencimize ait rapor hazırdır.&#10;{not}&#10;PDF Rapor: {pdf_link}">{{ old('message_template') }}</textarea>
                            <p class="mt-1 text-xs text-slate-500">Kullanılabilir alanlar: <code>{veli_adi}</code>, <code>{ogrenci_adi}</code>, <code>{not}</code>, <code>{pdf_link}</code></p>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Mesaj Notu {{ $selectedTargetType === 'parent_of_students' ? '(Opsiyonel)' : '(Zorunlu)' }}</label>
                        <textarea name="message_note" rows="3" class="w-full rounded-lg border-slate-300" placeholder="Alıcılara iletilecek mesaj">{{ old('message_note') }}</textarea>
                    </div>

                    <button class="rounded-lg px-4 py-2 text-sm font-semibold" style="background:#16a34a;color:#fff;border:1px solid #15803d;">
                        {{ $selectedTargetType === 'parent_of_students' ? 'Hazır WhatsApp Mesajlarını Oluştur' : 'Hazır Mesajları Oluştur' }}
                    </button>
                </form>
            </section>

            @if(isset($manualLinks) && $manualLinks->count() > 0)
                <section class="rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="font-semibold text-slate-800">Hazır WhatsApp Mesajları</h3>
                        <button type="button" id="open_all_whatsapp_links" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            Tümünü Aç
                        </button>
                    </div>
                    <div class="mt-3 space-y-2">
                        @foreach($manualLinks as $item)
                            <div class="wa-manual-item rounded-lg border border-slate-200 p-3 text-sm">
                                <div class="flex items-center justify-between gap-2">
                                    <div>
                                        <p class="font-semibold text-slate-800">{{ $item['receiver_name'] ?? '-' }} <span class="text-slate-500">({{ $item['phone'] ?? '-' }})</span></p>
                                        @if(!empty($item['student_name']))
                                            <p class="text-xs text-slate-500">Öğrenci: {{ $item['student_name'] }}</p>
                                        @endif
                                    </div>
                                    <a href="{{ $item['wa_link'] }}" target="_blank" class="wa-manual-link rounded-lg px-3 py-1.5 text-xs font-semibold" style="background:#0f172a;color:#fff;border:1px solid #0f172a;">WhatsApp'ta Aç</a>
                                </div>
                                @if(!empty($item['pdf_link']))
                                    <p class="mt-2 text-xs text-slate-500 break-all">Rapor: <a href="{{ $item['pdf_link'] }}" target="_blank" class="text-sky-700 underline">{{ $item['pdf_link'] }}</a></p>
                                @endif
                                <textarea rows="3" class="mt-2 w-full rounded-lg border-slate-300 text-xs">{{ $item['message'] ?? '' }}</textarea>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

        </div>
    </div>

    <script>
        (function () {
            const allBtn = document.getElementById('open_all_whatsapp_links');
            const linksContainer = document.querySelector('.space-y-2');

            function removeItemForLink(linkElement) {
                const row = linkElement.closest('.wa-manual-item');
                if (row) {
                    row.remove();
                }
            }

            document.querySelectorAll('a.wa-manual-link').forEach((link) => {
                link.addEventListener('click', function () {
                    removeItemForLink(link);
                });
            });

            if (allBtn) {
                allBtn.addEventListener('click', function () {
                    const links = Array.from(document.querySelectorAll('a.wa-manual-link'))
                        .map((a) => ({ href: a.getAttribute('href'), element: a }))
                        .filter((item) => item.href && item.href.startsWith('https://wa.me/'));
                    links.forEach((item) => {
                        window.open(item.href, '_blank');
                        removeItemForLink(item.element);
                    });
                });
            }

        })();
    </script>
</x-app-layout>



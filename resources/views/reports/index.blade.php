<x-app-layout>
    <x-slot name="header">Raporlar</x-slot>

    <div class="space-y-6">
        <div class="grid md:grid-cols-2 xl:grid-cols-5 gap-4">
            <div class="rounded-2xl p-4 shadow-sm border border-indigo-200" style="background:linear-gradient(135deg,#e0e7ff 0%,#c7d2fe 100%);">
                <p class="text-xs text-indigo-700 font-semibold">Toplam Öğrenci</p>
                <h3 class="text-2xl font-bold text-indigo-950 mt-1">{{ $students }}</h3>
            </div>
            <div class="rounded-2xl p-4 shadow-sm border border-cyan-200" style="background:linear-gradient(135deg,#cffafe 0%,#a5f3fc 100%);">
                <p class="text-xs text-cyan-700 font-semibold">Toplam Öğretmen</p>
                <h3 class="text-2xl font-bold text-cyan-950 mt-1">{{ $teachers }}</h3>
            </div>
            <div class="rounded-2xl p-4 shadow-sm border border-violet-200" style="background:linear-gradient(135deg,#ede9fe 0%,#ddd6fe 100%);">
                <p class="text-xs text-violet-700 font-semibold">Toplam Ödev</p>
                <h3 class="text-2xl font-bold text-violet-950 mt-1">{{ $assignments }}</h3>
            </div>
            <div class="rounded-2xl p-4 shadow-sm border border-emerald-200" style="background:linear-gradient(135deg,#dcfce7 0%,#bbf7d0 100%);">
                <p class="text-xs text-emerald-700 font-semibold">Teslim</p>
                <h3 class="text-2xl font-bold text-emerald-950 mt-1">{{ $submissions }}</h3>
            </div>
            <div class="rounded-2xl p-4 shadow-sm border border-amber-200" style="background:linear-gradient(135deg,#fef3c7 0%,#fde68a 100%);">
                <p class="text-xs text-amber-700 font-semibold">Tamamlama</p>
                <h3 class="text-2xl font-bold text-amber-950 mt-1">%{{ $completionRate }}</h3>
            </div>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100">
                <h3 class="text-2xl font-semibold text-slate-800">Hızlı Raporlar</h3>
                <p class="text-slate-400 mt-1">Tek tıkla hazır raporlar</p>
            </div>
            <div class="p-6 grid grid-cols-1 xl:grid-cols-3 gap-6">
                <article class="rounded-3xl bg-slate-50 border border-slate-100 p-6">
                    <div class="mx-auto w-24 h-24 rounded-3xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white text-3xl shadow-sm">📄</div>
                    <h4 class="mt-4 text-center text-2xl font-semibold text-slate-800">Öğrenci Listesi</h4>
                    <p class="text-center text-slate-400">PDF formatında</p>
                    <p class="text-center text-xs text-slate-500 mt-1">Toplam: {{ $quickReportStats['students_pdf'] }}</p>
                    <form method="GET" action="{{ route('reports.quick.student-pdf') }}" class="mt-4">
                        <select name="class_id" class="w-full rounded-2xl border-slate-300">
                            <option value="all">Tüm Sınıflar</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                        <button class="mt-3 w-full rounded-2xl py-3 text-lg font-semibold" style="background:linear-gradient(90deg,#3b82f6,#2563eb);color:#ffffff;border:1px solid #1d4ed8;">PDF İndir</button>
                    </form>
                </article>

                <article class="rounded-3xl bg-slate-50 border border-slate-100 p-6">
                    <div class="mx-auto w-24 h-24 rounded-3xl bg-gradient-to-br from-emerald-500 to-green-400 flex items-center justify-center text-white text-3xl shadow-sm">📊</div>
                    <h4 class="mt-4 text-center text-2xl font-semibold text-slate-800">Öğrenci Listesi</h4>
                    <p class="text-center text-slate-400">Excel formatında</p>
                    <p class="text-center text-xs text-slate-500 mt-1">Toplam: {{ $quickReportStats['students_excel'] }}</p>
                    <div class="mt-4">
                        <select class="w-full rounded-2xl border-slate-300">
                            <option value="all">Tüm Sınıflar</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="mt-3 w-full rounded-2xl py-3 text-lg font-semibold" style="background:linear-gradient(90deg,#22c55e,#16a34a);color:#ffffff;border:1px solid #15803d;">Excel</button>
                </article>

                <article class="rounded-3xl bg-slate-50 border border-slate-100 p-6">
                    <div class="mx-auto w-24 h-24 rounded-3xl bg-gradient-to-br from-sky-500 to-cyan-500 flex items-center justify-center text-white text-3xl shadow-sm">📋</div>
                    <h4 class="mt-4 text-center text-2xl font-semibold text-slate-800">Yoklama Listesi</h4>
                    <p class="text-center text-slate-400">Ders sayısı seçilebilir</p>
                    <p class="text-center text-xs text-slate-500 mt-1">Bugün alınan: {{ $quickReportStats['attendance_pdf'] }}</p>
                    <form method="GET" action="{{ route('reports.quick.attendance-pdf') }}" class="mt-4">
                        <select name="lesson_count" class="w-full rounded-2xl border-slate-300">
                            @foreach($lessonCountOptions as $count)
                                <option value="{{ $count }}">{{ $count }} Ders</option>
                            @endforeach
                        </select>
                        <button class="mt-3 w-full rounded-2xl py-3 text-lg font-semibold" style="background:linear-gradient(90deg,#7c3aed,#6d28d9);color:#ffffff;border:1px solid #5b21b6;">PDF İndir</button>
                    </form>
                </article>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white overflow-hidden" x-data="{ classFilter:'', fieldFilter:'' }">
            <div class="px-6 py-5 border-b border-slate-100">
                <h3 class="text-2xl font-semibold text-slate-800">Gelişmiş Rapor Ayarları</h3>
                <p class="text-slate-400 mt-1">Alan seçimi, sıralama, format ve özel ayarlar</p>
            </div>
            <form method="GET" action="{{ route('reports.index') }}" class="p-6 grid grid-cols-1 xl:grid-cols-3 gap-6">
                <article class="rounded-3xl border border-slate-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-500 to-violet-600 text-white px-4 py-3 flex items-center justify-between">
                        <h4 class="text-lg font-semibold">Sınıf Seçimi</h4>
                        <span class="text-xs bg-blue-400/80 rounded-full px-2 py-1">{{ $selectedClassIds->count() }} seçili</span>
                    </div>
                    <div class="p-4">
                        <input type="text" x-model="classFilter" placeholder="Filter" class="w-full rounded-xl border-slate-300">
                        <div class="mt-3 max-h-72 overflow-auto space-y-2">
                            @foreach($classes as $class)
                                <label x-show="'{{ mb_strtolower($class->name) }}'.includes(classFilter.toLowerCase())" class="flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" name="class_ids[]" value="{{ $class->id }}" class="rounded border-slate-300" @checked($selectedClassIds->contains($class->id))>
                                    <span>{{ $class->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </article>

                <article class="rounded-3xl border border-slate-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-500 to-violet-600 text-white px-4 py-3 flex items-center justify-between">
                        <h4 class="text-lg font-semibold">Rapor Alanları</h4>
                        <span class="text-xs bg-emerald-400/90 rounded-full px-2 py-1">{{ $selectedFields->count() }} alan</span>
                    </div>
                    <div class="p-4">
                        <input type="text" x-model="fieldFilter" placeholder="Filter" class="w-full rounded-xl border-slate-300">
                        <div class="mt-3 max-h-72 overflow-auto space-y-2">
                            @foreach($reportFieldOptions as $key => $label)
                                <label x-show="'{{ mb_strtolower($label) }}'.includes(fieldFilter.toLowerCase())" class="flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" name="fields[]" value="{{ $key }}" class="rounded border-slate-300" @checked($selectedFields->contains($key))>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </article>

                <article class="rounded-3xl border border-slate-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-500 to-violet-600 text-white px-4 py-3">
                        <h4 class="text-lg font-semibold">Rapor Ayarları</h4>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">Rapor Türü</label>
                            <select name="report_type" class="w-full rounded-xl border-slate-300">
                                <option value="student_list" @selected($reportType==='student_list')>Öğrenci Listesi</option>
                                <option value="performance" @selected($reportType==='performance')>Başarı Özeti</option>
                                <option value="attendance" @selected($reportType==='attendance')>Yoklama Özeti</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">Sıralama</label>
                            <select name="sort_by" class="w-full rounded-xl border-slate-300">
                                <option value="name" @selected($sortBy==='name')>Ada Göre</option>
                                <option value="number" @selected($sortBy==='number')>Numaraya Göre</option>
                                <option value="score" @selected($sortBy==='score')>Puana Göre</option>
                            </select>
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" name="landscape" value="1" class="rounded border-slate-300" @checked((bool)request('landscape'))>
                            Yatay Sayfa
                        </label>
                        <button class="w-full rounded-xl bg-blue-500 hover:bg-blue-600 text-white py-3 text-lg font-semibold">Rapor Oluştur</button>
                    </div>
                </article>
            </form>
        </section>
    </div>
</x-app-layout>

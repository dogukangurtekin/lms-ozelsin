<x-app-layout>
    <x-slot name="header">Raporlar</x-slot>

    <div class="space-y-6" x-data="{ reportTab: 'general', examTab: 'entry-card' }">
        <section class="rounded-2xl border border-slate-200 bg-white p-3">
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    @click="reportTab='general'"
                    :class="reportTab === 'general' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-300'"
                    class="inline-flex items-center rounded-xl border px-4 py-2 text-sm font-semibold transition"
                >
                    Genel Raporlar
                </button>
                <button
                    type="button"
                    @click="reportTab='exam'"
                    :class="reportTab === 'exam' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-300'"
                    class="inline-flex items-center rounded-xl border px-4 py-2 text-sm font-semibold transition"
                >
                    Sınav İşlemleri
                </button>
            </div>
        </section>

        <div x-show="reportTab === 'general'" x-cloak class="space-y-6">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-2xl border border-indigo-200 p-4 shadow-sm" style="background:linear-gradient(135deg,#e0e7ff 0%,#c7d2fe 100%);">
                    <p class="text-xs font-semibold text-indigo-700">Toplam Öğrenci</p>
                    <h3 class="mt-1 text-2xl font-bold text-indigo-950">{{ $students }}</h3>
                </div>
                <div class="rounded-2xl border border-cyan-200 p-4 shadow-sm" style="background:linear-gradient(135deg,#cffafe 0%,#a5f3fc 100%);">
                    <p class="text-xs font-semibold text-cyan-700">Toplam Öğretmen</p>
                    <h3 class="mt-1 text-2xl font-bold text-cyan-950">{{ $teachers }}</h3>
                </div>
                <div class="rounded-2xl border border-violet-200 p-4 shadow-sm" style="background:linear-gradient(135deg,#ede9fe 0%,#ddd6fe 100%);">
                    <p class="text-xs font-semibold text-violet-700">Toplam Ödev</p>
                    <h3 class="mt-1 text-2xl font-bold text-violet-950">{{ $assignments }}</h3>
                </div>
                <div class="rounded-2xl border border-emerald-200 p-4 shadow-sm" style="background:linear-gradient(135deg,#dcfce7 0%,#bbf7d0 100%);">
                    <p class="text-xs font-semibold text-emerald-700">Teslim</p>
                    <h3 class="mt-1 text-2xl font-bold text-emerald-950">{{ $submissions }}</h3>
                </div>
                <div class="rounded-2xl border border-amber-200 p-4 shadow-sm" style="background:linear-gradient(135deg,#fef3c7 0%,#fde68a 100%);">
                    <p class="text-xs font-semibold text-amber-700">Tamamlama</p>
                    <h3 class="mt-1 text-2xl font-bold text-amber-950">%{{ $completionRate }}</h3>
                </div>
            </div>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h3 class="text-2xl font-semibold text-slate-800">Hızlı Raporlar</h3>
                    <p class="mt-1 text-slate-400">Tek tıkla hazır raporlar</p>
                </div>
                <div class="grid grid-cols-1 gap-6 p-6 xl:grid-cols-3">
                    <article class="rounded-3xl border border-slate-100 bg-slate-50 p-6">
                        <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-3xl bg-gradient-to-br from-indigo-500 to-violet-600 text-3xl text-white shadow-sm">PDF</div>
                        <h4 class="mt-4 text-center text-2xl font-semibold text-slate-800">Öğrenci Listesi</h4>
                        <p class="text-center text-slate-400">PDF formatında</p>
                        <p class="mt-1 text-center text-xs text-slate-500">Toplam: {{ $quickReportStats['students_pdf'] }}</p>
                        <form method="GET" action="{{ route('reports.quick.student-pdf') }}" class="mt-4">
                            <select name="class_id" class="w-full rounded-2xl border-slate-300">
                                <option value="all">Tüm Sınıflar</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                                @endforeach
                            </select>
                            <button class="mt-3 w-full rounded-2xl py-3 text-lg font-semibold text-white" style="background:linear-gradient(90deg,#3b82f6,#2563eb);border:1px solid #1d4ed8;">PDF İndir</button>
                        </form>
                    </article>

                    <article class="rounded-3xl border border-slate-100 bg-slate-50 p-6">
                        <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-3xl bg-gradient-to-br from-emerald-500 to-green-400 text-3xl text-white shadow-sm">XLS</div>
                        <h4 class="mt-4 text-center text-2xl font-semibold text-slate-800">Öğrenci Listesi</h4>
                        <p class="text-center text-slate-400">Excel formatında</p>
                        <p class="mt-1 text-center text-xs text-slate-500">Toplam: {{ $quickReportStats['students_excel'] }}</p>
                        <div class="mt-4">
                            <select class="w-full rounded-2xl border-slate-300">
                                <option value="all">Tüm Sınıflar</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="mt-3 w-full rounded-2xl py-3 text-lg font-semibold text-white" style="background:linear-gradient(90deg,#22c55e,#16a34a);border:1px solid #15803d;">Excel</button>
                    </article>

                    <article class="rounded-3xl border border-slate-100 bg-slate-50 p-6">
                        <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-3xl bg-gradient-to-br from-sky-500 to-cyan-500 text-3xl text-white shadow-sm">YOK</div>
                        <h4 class="mt-4 text-center text-2xl font-semibold text-slate-800">Yoklama Listesi</h4>
                        <p class="text-center text-slate-400">Ders sayısı seçilebilir</p>
                        <p class="mt-1 text-center text-xs text-slate-500">Bugün alınan: {{ $quickReportStats['attendance_pdf'] }}</p>
                        <form method="GET" action="{{ route('reports.quick.attendance-pdf') }}" class="mt-4">
                            <select name="lesson_count" class="w-full rounded-2xl border-slate-300">
                                @foreach($lessonCountOptions as $count)
                                    <option value="{{ $count }}">{{ $count }} Ders</option>
                                @endforeach
                            </select>
                            <button class="mt-3 w-full rounded-2xl py-3 text-lg font-semibold text-white" style="background:linear-gradient(90deg,#7c3aed,#6d28d9);border:1px solid #5b21b6;">PDF İndir</button>
                        </form>
                    </article>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white" x-data="{ classFilter:'', fieldFilter:'' }">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h3 class="text-2xl font-semibold text-slate-800">Gelişmiş Rapor Ayarları</h3>
                    <p class="mt-1 text-slate-400">Alan seçimi, sıralama, format ve özel ayarlar</p>
                </div>
                <form method="GET" action="{{ route('reports.index') }}" class="grid grid-cols-1 gap-6 p-6 xl:grid-cols-3">
                    <article class="overflow-hidden rounded-3xl border border-slate-200">
                        <div class="flex items-center justify-between bg-gradient-to-r from-indigo-500 to-violet-600 px-4 py-3 text-white">
                            <h4 class="text-lg font-semibold">Sınıf Seçimi</h4>
                            <span class="rounded-full bg-blue-400/80 px-2 py-1 text-xs">{{ $selectedClassIds->count() }} seçili</span>
                        </div>
                        <div class="p-4">
                            <input type="text" x-model="classFilter" placeholder="Filter" class="w-full rounded-xl border-slate-300">
                            <div class="mt-3 max-h-72 space-y-2 overflow-auto">
                                @foreach($classes as $class)
                                    <label x-show="'{{ mb_strtolower($class->name) }}'.includes(classFilter.toLowerCase())" class="flex items-center gap-2 text-sm text-slate-700">
                                        <input type="checkbox" name="class_ids[]" value="{{ $class->id }}" class="rounded border-slate-300" @checked($selectedClassIds->contains($class->id))>
                                        <span>{{ $class->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </article>

                    <article class="overflow-hidden rounded-3xl border border-slate-200">
                        <div class="flex items-center justify-between bg-gradient-to-r from-indigo-500 to-violet-600 px-4 py-3 text-white">
                            <h4 class="text-lg font-semibold">Rapor Alanlar</h4>
                            <span class="rounded-full bg-emerald-400/90 px-2 py-1 text-xs">{{ $selectedFields->count() }} alan</span>
                        </div>
                        <div class="p-4">
                            <input type="text" x-model="fieldFilter" placeholder="Filter" class="w-full rounded-xl border-slate-300">
                            <div class="mt-3 max-h-72 space-y-2 overflow-auto">
                                @foreach($reportFieldOptions as $key => $label)
                                    <label x-show="'{{ mb_strtolower($label) }}'.includes(fieldFilter.toLowerCase())" class="flex items-center gap-2 text-sm text-slate-700">
                                        <input type="checkbox" name="fields[]" value="{{ $key }}" class="rounded border-slate-300" @checked($selectedFields->contains($key))>
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </article>

                    <article class="overflow-hidden rounded-3xl border border-slate-200">
                        <div class="bg-gradient-to-r from-indigo-500 to-violet-600 px-4 py-3 text-white">
                            <h4 class="text-lg font-semibold">Rapor Ayarlar</h4>
                        </div>
                        <div class="space-y-4 p-4">
                            <div>
                                <label class="mb-1 block text-sm text-slate-600">Rapor Türü</label>
                                <select name="report_type" class="w-full rounded-xl border-slate-300">
                                    <option value="student_list" @selected($reportType==='student_list')>Öğrenci Listesi</option>
                                    <option value="performance" @selected($reportType==='performance')>Başarı Özeti</option>
                                    <option value="attendance" @selected($reportType==='attendance')>Yoklama Özeti</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm text-slate-600">Sıralama</label>
                                <select name="sort_by" class="w-full rounded-xl border-slate-300">
                                    <option value="name" @selected($sortBy==='name')>Ada Göre</option>
                                    <option value="number" @selected($sortBy==='number')>Numaraya Göre</option>
                                    <option value="score" @selected($sortBy==='score')>Puana Göre</option>
                                </select>
                            </div>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                                <input type="checkbox" name="landscape" value="1" class="rounded border-slate-300" @checked((bool) request('landscape'))>
                                Yatay Sayfa
                            </label>
                            <button class="w-full rounded-xl bg-blue-500 py-3 text-lg font-semibold text-white hover:bg-blue-600">Rapor Oluştur</button>
                        </div>
                    </article>
                </form>
            </section>
        </div>

        <div x-show="reportTab === 'exam'" x-cloak class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-3">
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        @click="examTab='entry-card'"
                        :class="examTab === 'entry-card' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-700 border-slate-300'"
                        class="inline-flex items-center rounded-xl border px-4 py-2 text-sm font-semibold transition"
                    >
                        Sınav Giriş Belgesi
                    </button>
                </div>
            </section>

            <section x-show="examTab === 'entry-card'" x-cloak class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h3 class="text-2xl font-semibold text-slate-800">Sınav İşlemleri</h3>
                    <p class="mt-1 text-slate-400">Sınav Giriş Belgesi ekranı. Rastgele salon ve sıra yerleşimi ile kurumsal PDF paketi üretir.</p>
                </div>

                <form method="POST" action="{{ request()->getBaseUrl() . route('reports.exam-entry-package', [], false) }}" enctype="multipart/form-data" class="exam-entry-form space-y-6 p-6">
                    @csrf

                    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                        <article class="rounded-3xl border border-slate-200 p-5">
                            <h4 class="text-lg font-semibold text-slate-900">Sınav Bilgileri</h4>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label class="mb-1 block text-sm text-slate-600">Kurum Adı</label>
                                    <input type="text" name="school_name" value="{{ old('school_name', 'Özelsin Eğitim Kurumları') }}" class="w-full rounded-xl border-slate-300">
                                </div>
                            </div>
                        </article>

                        <article class="rounded-3xl border border-slate-200 p-5">
                            <h4 class="text-lg font-semibold text-slate-900">Tarih ve Yer</h4>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label class="mb-1 block text-sm text-slate-600">Sınav Tarihi</label>
                                    <input type="date" name="exam_date" value="{{ old('exam_date') }}" class="w-full rounded-xl border-slate-300" required>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm text-slate-600">Sınav Saati</label>
                                    <input type="text" name="exam_time" value="{{ old('exam_time') }}" class="w-full rounded-xl border-slate-300" placeholder="Örn: 10:30">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm text-slate-600">Adres</label>
                                    <textarea name="exam_address" rows="4" class="w-full rounded-xl border-slate-300" placeholder="Örn: Merkez Kampüs, Mahalle / Sokak / No">{{ old('exam_address') }}</textarea>
                                </div>
                            </div>
                        </article>

                        <article class="rounded-3xl border border-slate-200 p-5">
                            <h4 class="text-lg font-semibold text-slate-900">Öğrenci Excel'i</h4>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label class="mb-1 block text-sm text-slate-600">Ad Soyad Kolon Başlığı</label>
                                    <input type="text" name="name_column" value="{{ old('name_column', 'ad_soyad') }}" class="w-full rounded-xl border-slate-300" placeholder="Örn: ad_soyad">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm text-slate-600">Excel / CSV Dosyası</label>
                                    <input type="file" name="import_file" accept=".csv,.txt,.xls,.xlsx" class="w-full rounded-xl border-slate-300" required>
                                    <p class="mt-2 text-xs text-slate-500">Öğrenci adı soyadı tek hücrede olabilir. Sistem belirtilen kolonu, bulamazsa ilk dolu kolonu kullanır.</p>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.25fr_0.75fr]">
                        <article class="rounded-3xl border border-slate-200 p-5">
                            <h4 class="text-lg font-semibold text-slate-900">Salon / Sınıf Yerleşimi</h4>
                            <p class="mt-1 text-sm text-slate-500">Sistemde kayıtlı sınıf/şubeler otomatik gelir. Her satırda sadece sınıf mevcudu girin.</p>
                            <div
                                class="mt-4"
                                data-room-class-list='@json(($examRoomClasses ?? collect())->values())'
                                data-old-room-definitions='@json(old("room_definitions", ""))'
                                data-room-rows-host
                            ></div>
                            <div class="mt-3 grid grid-cols-1 gap-2 md:grid-cols-[120px_1fr_auto]">
                                <select class="w-full rounded-xl border-slate-300" data-add-grade-select>
                                    <option value="">Kademe</option>
                                    <option value="5">5</option>
                                    <option value="6">6</option>
                                    <option value="7">7</option>
                                    <option value="8">8</option>
                                    <option value="9">9</option>
                                    <option value="10">10</option>
                                    <option value="11">11</option>
                                    <option value="12">12</option>
                                </select>
                                <select class="w-full rounded-xl border-slate-300" data-add-class-select></select>
                                <button type="button" class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" data-add-class-btn>Sınıf Ekle</button>
                            </div>
                            <input type="hidden" name="room_definitions" class="room-definitions-hidden" value="{{ old('room_definitions') }}" required>
                        </article>

                        <article class="rounded-3xl border border-slate-200 p-5">
                            <h4 class="text-lg font-semibold text-slate-900">Belge Notu</h4>
                            <textarea name="exam_notes" rows="10" class="mt-4 w-full rounded-2xl border-slate-300" placeholder="Öğrencinin sınav saatinden 15 dakika önce okulda hazır bulunması gerekmektedir.">{{ old('exam_notes') }}</textarea>
                        </article>
                    </div>

                    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                        <article class="rounded-3xl border border-slate-200 p-5">
                            <h4 class="text-lg font-semibold text-slate-900">Birinci Oturum - Sözel Alan</h4>
                            <div class="mt-4 space-y-3">
                                <div>
                                    <label class="mb-1 block text-sm text-slate-600">Oturum Başlığı</label>
                                    <input type="text" name="session_one_title" value="{{ old('session_one_title', 'BİRİNCİ OTURUM - SÖZEL ALAN') }}" class="w-full rounded-xl border-slate-300">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm text-slate-600">Ders | Soru Sayısı (her satır)</label>
                                    <textarea name="session_one_subjects" rows="6" class="w-full rounded-xl border-slate-300" placeholder="Türkçe|20&#10;T.C. İnkılap Tarihi ve Atatürkçülük|10&#10;Din Kültürü ve Ahlak Bilgisi|10&#10;Yabancı Dil|10">{{ old('session_one_subjects', "Türkçe|20\nT.C. İnkılap Tarihi ve Atatürkçülük|10\nDin Kültürü ve Ahlak Bilgisi|10\nYabancı Dil|10") }}</textarea>
                                </div>
                            </div>
                        </article>
                        <article class="rounded-3xl border border-slate-200 p-5">
                            <h4 class="text-lg font-semibold text-slate-900">İkinci Oturum - Sayısal Alan</h4>
                            <div class="mt-4 space-y-3">
                                <div>
                                    <label class="mb-1 block text-sm text-slate-600">Oturum Başlığı</label>
                                    <input type="text" name="session_two_title" value="{{ old('session_two_title', 'İKİNCİ OTURUM - SAYISAL ALAN') }}" class="w-full rounded-xl border-slate-300">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm text-slate-600">Ders | Soru Sayısı (her satır)</label>
                                    <textarea name="session_two_subjects" rows="6" class="w-full rounded-xl border-slate-300" placeholder="Matematik|20&#10;Fen Bilimleri|20">{{ old('session_two_subjects', "Matematik|20\nFen Bilimleri|20") }}</textarea>
                                </div>
                            </div>
                        </article>
                    </div>

                    <article class="rounded-3xl border border-slate-200 p-5">
                        <h4 class="text-lg font-semibold text-slate-900">Logo Önizleme ve Kırpma</h4>
                        <p class="mt-1 text-xs text-slate-500">Logoları büyük pencerede canlı görüp zoom/kaydırma ayarı yapın. PDF üstündeki logolar bu alandan oluşturulur.</p>
                        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2" data-logo-editor-root>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3" data-logo-editor data-logo-index="0" data-target-width="132" data-target-height="82">
                                <div class="mb-2 text-sm font-semibold text-slate-700">1. Logo (132x82)</div>
                                <input type="file" name="logo_files[]" accept="image/*" class="w-full rounded-xl border-slate-300" data-logo-file>
                                <input type="hidden" name="logo_payloads[]" data-logo-payload>
                                <div class="mt-3 flex justify-center">
                                    <div class="relative overflow-hidden rounded-lg border border-slate-300 bg-white" style="width:396px;height:246px;" data-logo-preview-box data-preview-width="396" data-preview-height="246">
                                        <img src="" alt="" class="hidden" style="position:absolute;left:50%;top:50%;max-width:none;max-height:none;transform:translate(-50%,-50%);" data-logo-preview-img>
                                        <div class="absolute inset-0 grid place-items-center text-[11px] text-slate-400" data-logo-placeholder>Önizleme</div>
                                    </div>
                                </div>
                                <div class="mt-3 space-y-2 text-xs">
                                    <label class="block text-slate-600">Zoom <input type="range" min="50" max="250" value="100" data-logo-zoom class="w-full"></label>
                                    <label class="block text-slate-600">Sol Kırpma <input type="range" min="0" max="45" value="0" data-logo-crop-left class="w-full"></label>
                                    <label class="block text-slate-600">Sağ Kırpma <input type="range" min="0" max="45" value="0" data-logo-crop-right class="w-full"></label>
                                    <label class="block text-slate-600">Üst Kırpma <input type="range" min="0" max="45" value="0" data-logo-crop-top class="w-full"></label>
                                    <label class="block text-slate-600">Alt Kırpma <input type="range" min="0" max="45" value="0" data-logo-crop-bottom class="w-full"></label>
                                </div>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3" data-logo-editor data-logo-index="1" data-target-width="124" data-target-height="82">
                                <div class="mb-2 text-sm font-semibold text-slate-700">2. Logo (124x82)</div>
                                <input type="file" name="logo_files[]" accept="image/*" class="w-full rounded-xl border-slate-300" data-logo-file>
                                <input type="hidden" name="logo_payloads[]" data-logo-payload>
                                <div class="mt-3 flex justify-center">
                                    <div class="relative overflow-hidden rounded-lg border border-slate-300 bg-white" style="width:372px;height:246px;" data-logo-preview-box data-preview-width="372" data-preview-height="246">
                                        <img src="" alt="" class="hidden" style="position:absolute;left:50%;top:50%;max-width:none;max-height:none;transform:translate(-50%,-50%);" data-logo-preview-img>
                                        <div class="absolute inset-0 grid place-items-center text-[11px] text-slate-400" data-logo-placeholder>Önizleme</div>
                                    </div>
                                </div>
                                <div class="mt-3 space-y-2 text-xs">
                                    <label class="block text-slate-600">Zoom <input type="range" min="50" max="250" value="100" data-logo-zoom class="w-full"></label>
                                    <label class="block text-slate-600">Sol Kırpma <input type="range" min="0" max="45" value="0" data-logo-crop-left class="w-full"></label>
                                    <label class="block text-slate-600">Sağ Kırpma <input type="range" min="0" max="45" value="0" data-logo-crop-right class="w-full"></label>
                                    <label class="block text-slate-600">Üst Kırpma <input type="range" min="0" max="45" value="0" data-logo-crop-top class="w-full"></label>
                                    <label class="block text-slate-600">Alt Kırpma <input type="range" min="0" max="45" value="0" data-logo-crop-bottom class="w-full"></label>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="rounded-3xl border border-slate-200 p-5">
                        <h4 class="text-lg font-semibold text-slate-900">Belge Şablonu Tasarım</h4>
                        <p class="mt-1 text-xs text-slate-500">PDF üretiminde kullanılacak belge stilini ve ana renkleri seçin.</p>
                        <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label class="mb-1 block text-sm text-slate-600">Şablon</label>
                                    <select name="exam_template" class="w-full rounded-xl border-slate-300" data-exam-template-select>
                                        <option value="modern" @selected(old('exam_template', 'modern') === 'modern')>Modern</option>
                                        <option value="classic" @selected(old('exam_template') === 'classic')>Klasik</option>
                                        <option value="minimal" @selected(old('exam_template') === 'minimal')>Minimal</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm text-slate-600">Ana Renk</label>
                                    <input type="color" name="theme_primary_color" value="{{ old('theme_primary_color', '#0f172a') }}" class="h-11 w-full rounded-xl border-slate-300 p-1" data-theme-primary-color>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm text-slate-600">Vurgu Rengi</label>
                                    <input type="color" name="theme_accent_color" value="{{ old('theme_accent_color', '#1d4ed8') }}" class="h-11 w-full rounded-xl border-slate-300 p-1" data-theme-accent-color>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="mb-1 block text-sm text-slate-600">Çerçeve Rengi</label>
                                    <input type="color" name="theme_border_color" value="{{ old('theme_border_color', '#cbd5e1') }}" class="h-11 w-full rounded-xl border-slate-300 p-1" data-theme-border-color>
                                </div>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                <div class="mb-2 text-sm font-semibold text-slate-700">Canlı Şablon Önizleme</div>
                                <div class="mx-auto max-w-[340px] rounded-xl bg-white p-3 shadow-sm" data-template-preview-card style="border:2px solid #0f172a;">
                                    <div class="mb-2 rounded-lg px-3 py-2 text-center text-[11px] font-bold tracking-wide text-white" data-template-preview-title style="background:#0f172a;">
                                        SINAV GİRİŞ BELGESİ
                                    </div>
                                    <div class="rounded-lg border p-2" data-template-preview-stack style="border-color:#cbd5e1;">
                                        <div class="text-center text-[10px] font-semibold text-slate-600">Öğrenci Adı Soyadı</div>
                                        <div class="text-center text-sm font-bold text-slate-900">ÖRNEK ÖĞRENCİ</div>
                                        <div class="mt-2 border-t border-dashed pt-2 text-center text-[10px] text-slate-600">Sınıf / Şube: 7-A | Sıra: 12</div>
                                    </div>
                                    <div class="mt-2 rounded border px-2 py-1 text-center text-[10px] font-semibold text-slate-700" data-template-preview-tag style="border-color:#cbd5e1;background:#f8fafc;">
                                        Örnek Oturum Tablosu
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <div class="text-sm font-semibold text-slate-800">Çıktılar</div>
                            <div class="mt-1 text-xs text-slate-500">Zip içinde profesyonel PDF sınav giriş belgeleri ve sınıf/sıra yerleşim Excel'i hazırlanır.</div>
                        </div>
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl px-6 py-3 text-base font-semibold text-white shadow-sm" style="background:linear-gradient(90deg,#0f172a,#1d4ed8);border:1px solid #1e3a8a;">
                            PDF'leri Oluştur
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <div id="examProgressBox" class="mobile-progress-box fixed bottom-5 right-5 z-50 hidden w-80 rounded-2xl border border-slate-200 bg-white p-4 shadow-lg">
        <div class="flex items-center justify-between gap-3">
            <p id="examProgressLabel" class="text-sm font-semibold text-slate-800">Sınav giriş paketi hazırlanıyor...</p>
            <span id="examProgressPercent" class="text-xs font-semibold text-slate-500">%0</span>
        </div>
        <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-slate-200">
            <div id="examProgressBar" class="h-full rounded-full bg-blue-600 transition-all duration-300" style="width:0%"></div>
        </div>
        <p id="examProgressText" class="mt-2 text-xs text-slate-500">Dosya bekleniyor...</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <script>
        function initLogoEditors(form) {
            const editors = Array.from(form.querySelectorAll('[data-logo-editor]'));
            const editorState = [];
            if (!editors.length) return editorState;

            const renderPreview = (state) => {
                const { imgEl, placeholderEl, zoomEl, cropLeftEl, cropRightEl, cropTopEl, cropBottomEl, image, previewWidth, previewHeight } = state;
                if (!image) {
                    imgEl.classList.add('hidden');
                    placeholderEl.classList.remove('hidden');
                    imgEl.removeAttribute('src');
                    imgEl.style.clipPath = 'none';
                    return;
                }
                const zoomRaw = parseInt(zoomEl.value || '100', 10) || 100;
                const zoom = zoomRaw / 100;
                const cropLeft = Math.max(0, Math.min(45, parseInt(cropLeftEl?.value || '0', 10) || 0));
                const cropRight = Math.max(0, Math.min(45, parseInt(cropRightEl?.value || '0', 10) || 0));
                const cropTop = Math.max(0, Math.min(45, parseInt(cropTopEl?.value || '0', 10) || 0));
                const cropBottom = Math.max(0, Math.min(45, parseInt(cropBottomEl?.value || '0', 10) || 0));
                imgEl.src = image.src;
                const fitScale = Math.min(previewWidth / image.width, previewHeight / image.height);
                const drawScale = fitScale * zoom;
                imgEl.style.width = `${Math.round(image.width * drawScale)}px`;
                imgEl.style.height = `${Math.round(image.height * drawScale)}px`;
                imgEl.style.transform = 'translate(-50%, -50%)';
                imgEl.style.clipPath = `inset(${cropTop}% ${cropRight}% ${cropBottom}% ${cropLeft}%)`;
                imgEl.classList.remove('hidden');
                placeholderEl.classList.add('hidden');
            };

            editors.forEach((editor, index) => {
                const fileEl = editor.querySelector('[data-logo-file]');
                const payloadEl = editor.querySelector('[data-logo-payload]');
                const imgEl = editor.querySelector('[data-logo-preview-img]');
                const placeholderEl = editor.querySelector('[data-logo-placeholder]');
                const zoomEl = editor.querySelector('[data-logo-zoom]');
                const cropLeftEl = editor.querySelector('[data-logo-crop-left]');
                const cropRightEl = editor.querySelector('[data-logo-crop-right]');
                const cropTopEl = editor.querySelector('[data-logo-crop-top]');
                const cropBottomEl = editor.querySelector('[data-logo-crop-bottom]');
                const previewBox = editor.querySelector('[data-logo-preview-box]');
                const targetWidth = parseInt(editor.getAttribute('data-target-width') || '124', 10) || 124;
                const targetHeight = parseInt(editor.getAttribute('data-target-height') || '82', 10) || 82;
                const previewWidth = parseInt(previewBox?.getAttribute('data-preview-width') || String(targetWidth), 10) || targetWidth;
                const previewHeight = parseInt(previewBox?.getAttribute('data-preview-height') || String(targetHeight), 10) || targetHeight;

                const state = { index, editor, fileEl, payloadEl, imgEl, placeholderEl, zoomEl, cropLeftEl, cropRightEl, cropTopEl, cropBottomEl, targetWidth, targetHeight, previewWidth, previewHeight, image: null };
                editorState.push(state);

                fileEl?.addEventListener('change', () => {
                    const file = fileEl.files?.[0];
                    if (!file) {
                        state.image = null;
                        if (payloadEl) payloadEl.value = '';
                        renderPreview(state);
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = () => {
                        const img = new Image();
                        img.onload = () => {
                            state.image = img;
                            if (zoomEl) zoomEl.value = '100';
                            if (cropLeftEl) cropLeftEl.value = '0';
                            if (cropRightEl) cropRightEl.value = '0';
                            if (cropTopEl) cropTopEl.value = '0';
                            if (cropBottomEl) cropBottomEl.value = '0';
                            renderPreview(state);
                        };
                        img.src = String(reader.result || '');
                    };
                    reader.readAsDataURL(file);
                });

                [zoomEl, cropLeftEl, cropRightEl, cropTopEl, cropBottomEl].forEach((el) => el?.addEventListener('input', () => renderPreview(state)));
            });

            return editorState;
        }

        async function generateLogoPayloads(editorState) {
            const tasks = editorState.map((state) => new Promise((resolve) => {
                const { payloadEl, image, zoomEl, cropLeftEl, cropRightEl, cropTopEl, cropBottomEl, targetWidth, targetHeight } = state;
                if (!payloadEl) return resolve();
                if (!image) {
                    payloadEl.value = '';
                    return resolve();
                }
                const zoom = (parseInt(zoomEl.value || '100', 10) || 100) / 100;
                const cropLeft = Math.max(0, Math.min(45, parseInt(cropLeftEl?.value || '0', 10) || 0));
                const cropRight = Math.max(0, Math.min(45, parseInt(cropRightEl?.value || '0', 10) || 0));
                const cropTop = Math.max(0, Math.min(45, parseInt(cropTopEl?.value || '0', 10) || 0));
                const cropBottom = Math.max(0, Math.min(45, parseInt(cropBottomEl?.value || '0', 10) || 0));
                const cropXPerc = Math.min(90, cropLeft + cropRight);
                const cropYPerc = Math.min(90, cropTop + cropBottom);
                const sx = image.width * (cropLeft / 100);
                const sy = image.height * (cropTop / 100);
                const sw = Math.max(1, image.width * (1 - cropXPerc / 100));
                const sh = Math.max(1, image.height * (1 - cropYPerc / 100));

                const exportScale = 4;
                const canvas = document.createElement('canvas');
                canvas.width = targetWidth * exportScale;
                canvas.height = targetHeight * exportScale;
                const ctx = canvas.getContext('2d');
                if (!ctx) {
                    payloadEl.value = '';
                    return resolve();
                }

                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = 'high';
                ctx.scale(exportScale, exportScale);
                ctx.clearRect(0, 0, targetWidth, targetHeight);
                const fitScale = Math.min(targetWidth / sw, targetHeight / sh);
                const drawScale = fitScale * zoom;
                const drawW = sw * drawScale;
                const drawH = sh * drawScale;
                const drawX = (targetWidth - drawW) / 2;
                const drawY = (targetHeight - drawH) / 2;
                ctx.drawImage(image, sx, sy, sw, sh, drawX, drawY, drawW, drawH);

                payloadEl.value = canvas.toDataURL('image/png');
                resolve();
            }));

            await Promise.all(tasks);
        }

        function parseRoomDefinitionsMap(rawText) {
            const out = new Map();
            String(rawText || '').split(/\r?\n/).forEach((line) => {
                const row = String(line || '').trim();
                if (!row) return;
                const parts = row.split('|');
                const room = String(parts[0] || '').trim();
                const cap = Math.max(0, parseInt(String(parts[1] || '0').trim(), 10) || 0);
                if (!room) return;
                out.set(room, cap);
            });
            return out;
        }

        function normalizeRoomClassList(input) {
            if (!Array.isArray(input)) return [];
            const set = new Set();
            input.forEach((v) => {
                const name = String(v || '').trim();
                if (!name) return;
                set.add(name);
            });
            return Array.from(set).sort((a, b) => a.localeCompare(b, 'tr'));
        }

        function extractGradeLevel(roomName) {
            const match = String(roomName || '').trim().match(/^(\d{1,2})/);
            return match ? match[1] : '';
        }

        function syncRoomDefinitionsHidden(form) {
            const hidden = form.querySelector('.room-definitions-hidden');
            if (!hidden) return;
            const lines = Array.from(form.querySelectorAll('[data-room-row]'))
                .map((row) => {
                    const room = String(row.getAttribute('data-room-name') || '').trim();
                    const mobileSelect = row.querySelector('[data-room-capacity-select]');
                    const desktopInput = row.querySelector('[data-room-capacity-input]');
                    const isDesktop = window.matchMedia('(min-width: 768px)').matches;
                    const source = isDesktop ? desktopInput : mobileSelect;
                    const cap = Math.max(0, parseInt(String(source?.value || '0'), 10) || 0);
                    if (!room || cap <= 0) return '';
                    return `${room}|${cap}`;
                })
                .filter(Boolean);
            hidden.value = lines.join('\n');
        }

        function buildRoomRows(form) {
            const host = form.querySelector('[data-room-rows-host]');
            const hidden = form.querySelector('.room-definitions-hidden');
            const addGradeSelect = form.querySelector('[data-add-grade-select]');
            const addSelect = form.querySelector('[data-add-class-select]');
            const addBtn = form.querySelector('[data-add-class-btn]');
            if (!host || !hidden) return;

            const classList = normalizeRoomClassList(JSON.parse(host.getAttribute('data-room-class-list') || '[]'));
            const oldMap = parseRoomDefinitionsMap(host.getAttribute('data-old-room-definitions') || hidden.value || '');
            const selectedSet = new Set((oldMap.size ? Array.from(oldMap.keys()) : classList).filter((n) => classList.includes(n)));

            const renderAddOptions = () => {
                if (!addSelect) return;
                const selectedGrade = String(addGradeSelect?.value || '').trim();
                const remaining = classList.filter((name) => {
                    if (selectedSet.has(name)) return false;
                    if (!selectedGrade) return true;
                    return extractGradeLevel(name) === selectedGrade;
                });
                if (!remaining.length) {
                    addSelect.innerHTML = `<option value="">${selectedGrade ? 'Bu kademede eklenebilecek sınıf yok' : 'Eklenebilecek sınıf kalmadı'}</option>`;
                    addSelect.disabled = true;
                    if (addBtn) addBtn.disabled = true;
                    return;
                }
                addSelect.innerHTML = '<option value="">Sınıf seçin</option>' + remaining.map((name) => `<option value="${name}">${name}</option>`).join('');
                addSelect.disabled = false;
                if (addBtn) addBtn.disabled = false;
            };

            const renderRows = () => {
                const selectedRooms = classList.filter((name) => selectedSet.has(name));
                host.className = 'mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4';

                if (!selectedRooms.length) {
                    host.innerHTML = '<div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Sistemde sınıf/şube kaydı bulunamadı.</div>';
                    hidden.value = '';
                    renderAddOptions();
                    return;
                }

                host.innerHTML = selectedRooms.map((roomName) => {
                    const cap = oldMap.get(roomName) ?? 18;
                    const options = Array.from({ length: 60 }, (_, i) => i + 1)
                        .map((n) => `<option value="${n}" ${n === cap ? 'selected' : ''}>${n}</option>`)
                        .join('');
                    return `
                        <div data-room-row data-room-name="${roomName.replace(/"/g, '&quot;')}" class="grid grid-cols-[minmax(0,1fr)_56px_46px] items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2 py-1.5 overflow-hidden">
                            <div class="truncate rounded-md bg-white px-2 py-1 text-xs font-semibold text-slate-700 border border-slate-200" title="${roomName.replace(/"/g, '&quot;')}">${roomName}</div>
                            <select data-room-capacity-select class="w-full rounded-md border-slate-300 px-1 py-1 text-center text-xs font-semibold text-slate-800 md:hidden">${options}</select>
                            <input type="number" min="1" max="120" value="${cap}" data-room-capacity-input class="hidden w-full rounded-md border-slate-300 px-1 py-1 text-center text-xs font-semibold text-slate-800 md:block">
                            <button type="button" data-room-remove class="rounded-md border border-rose-200 px-1 py-1 text-[11px] font-semibold text-rose-700 hover:bg-rose-50">Sil</button>
                        </div>
                    `;
                }).join('');

                host.querySelectorAll('[data-room-row]').forEach((row) => {
                    const selectEl = row.querySelector('[data-room-capacity-select]');
                    const inputEl = row.querySelector('[data-room-capacity-input]');
                    const removeEl = row.querySelector('[data-room-remove]');

                    const syncBoth = (value) => {
                        const v = Math.max(1, Math.min(120, parseInt(String(value || '1'), 10) || 1));
                        if (selectEl) selectEl.value = String(v);
                        if (inputEl) inputEl.value = String(v);
                        oldMap.set(String(row.getAttribute('data-room-name') || ''), v);
                        syncRoomDefinitionsHidden(form);
                    };

                    selectEl?.addEventListener('change', () => syncBoth(selectEl.value));
                    inputEl?.addEventListener('input', () => syncBoth(inputEl.value));
                    removeEl?.addEventListener('click', () => {
                        const name = String(row.getAttribute('data-room-name') || '');
                        selectedSet.delete(name);
                        oldMap.delete(name);
                        renderRows();
                        renderAddOptions();
                    });
                });

                syncRoomDefinitionsHidden(form);
                renderAddOptions();
            };

            if (addBtn && addSelect) {
                addBtn.onclick = () => {
                    const name = String(addSelect.value || '').trim();
                    if (!name || selectedSet.has(name)) return;
                    selectedSet.add(name);
                    if (!oldMap.has(name)) oldMap.set(name, 18);
                    renderRows();
                };
            }

            addGradeSelect?.addEventListener('change', () => {
                const grade = String(addGradeSelect.value || '').trim();
                if (grade) {
                    classList.forEach((name) => {
                        if (extractGradeLevel(name) === grade && !selectedSet.has(name)) {
                            selectedSet.add(name);
                            if (!oldMap.has(name)) oldMap.set(name, 18);
                        }
                    });
                    renderRows();
                    return;
                }
                renderAddOptions();
            });

            renderRows();
        }

        function initTemplatePreview(form) {
            const templateEl = form.querySelector('[data-exam-template-select]');
            const primaryEl = form.querySelector('[data-theme-primary-color]');
            const accentEl = form.querySelector('[data-theme-accent-color]');
            const borderEl = form.querySelector('[data-theme-border-color]');
            const cardEl = form.querySelector('[data-template-preview-card]');
            const titleEl = form.querySelector('[data-template-preview-title]');
            const stackEl = form.querySelector('[data-template-preview-stack]');
            const tagEl = form.querySelector('[data-template-preview-tag]');
            if (!templateEl || !primaryEl || !accentEl || !borderEl || !cardEl || !titleEl || !stackEl || !tagEl) return;

            const apply = () => {
                const template = String(templateEl.value || 'modern');
                const primary = String(primaryEl.value || '#0f172a');
                const accent = String(accentEl.value || '#1d4ed8');
                const border = String(borderEl.value || '#cbd5e1');

                cardEl.style.border = `2px solid ${primary}`;
                stackEl.style.borderColor = border;
                tagEl.style.borderColor = border;

                if (template === 'classic') {
                    titleEl.style.background = `linear-gradient(90deg, ${primary}, ${accent})`;
                    titleEl.style.color = '#ffffff';
                    titleEl.style.border = 'none';
                    cardEl.style.borderRadius = '0.6rem';
                } else if (template === 'minimal') {
                    titleEl.style.background = '#f8fafc';
                    titleEl.style.color = primary;
                    titleEl.style.border = `1px solid ${border}`;
                    cardEl.style.borderRadius = '0.75rem';
                } else {
                    titleEl.style.background = primary;
                    titleEl.style.color = '#ffffff';
                    titleEl.style.border = 'none';
                    cardEl.style.borderRadius = '0.75rem';
                }
            };

            [templateEl, primaryEl, accentEl, borderEl].forEach((el) => el.addEventListener('input', apply));
            [templateEl, primaryEl, accentEl, borderEl].forEach((el) => el.addEventListener('change', apply));
            apply();
        }

        async function convertExamExcelFileToRows(file) {
            const ext = (file.name.split('.').pop() || '').toLowerCase();
            if (!['xls', 'xlsx'].includes(ext)) return null;

            const buffer = await file.arrayBuffer();
            const workbook = XLSX.read(buffer, { type: 'array' });
            const firstSheetName = workbook.SheetNames[0];
            if (!firstSheetName) return [];

            const sheet = workbook.Sheets[firstSheetName];
            return XLSX.utils.sheet_to_json(sheet, { defval: '' });
        }

        function updateExamProgress(percent, label, text) {
            const box = document.getElementById('examProgressBox');
            const bar = document.getElementById('examProgressBar');
            const percentNode = document.getElementById('examProgressPercent');
            const labelNode = document.getElementById('examProgressLabel');
            const textNode = document.getElementById('examProgressText');

            box?.classList.remove('hidden');
            if (bar) bar.style.width = `${percent}%`;
            if (percentNode) percentNode.textContent = `%${percent}`;
            if (labelNode && label) labelNode.textContent = label;
            if (textNode && text) textNode.textContent = text;
        }

        function hideExamProgress(delay = 1500) {
            const box = document.getElementById('examProgressBox');
            window.setTimeout(() => box?.classList.add('hidden'), delay);
        }

        function extractDownloadFilename(xhr) {
            const disposition = xhr.getResponseHeader('Content-Disposition') || '';
            const utf8Match = disposition.match(/filename\*=UTF-8''([^;]+)/i);
            if (utf8Match?.[1]) return decodeURIComponent(utf8Match[1]);

            const plainMatch = disposition.match(/filename="?([^"]+)"?/i);
            return plainMatch?.[1] || 'sinav-giris-paketi.zip';
        }

        document.querySelectorAll('.exam-entry-form').forEach((form) => {
            buildRoomRows(form);
            initTemplatePreview(form);
            const logoEditorState = initLogoEditors(form);
            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                syncRoomDefinitionsHidden(form);
                await generateLogoPayloads(logoEditorState);

                const fileInput = form.querySelector('input[type="file"][name="import_file"]');
                const file = fileInput?.files?.[0];
                if (!file) return;

                const parsedRows = await convertExamExcelFileToRows(file);
                if (!parsedRows) return;

                form.querySelectorAll('input[name="parsed_rows_json"]').forEach((node) => node.remove());
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'parsed_rows_json';
                hidden.value = JSON.stringify(parsedRows);
                form.appendChild(hidden);

                const xhr = new XMLHttpRequest();
                const formData = new FormData(form);
                const submitButton = form.querySelector('button[type="submit"]');
                let processingTimer = null;
                let processingValue = 8;

                if (submitButton) submitButton.disabled = true;

                updateExamProgress(4, 'Dosya hazırlanıyor...', 'Excel satırları çözümleniyor.');

                xhr.open('POST', form.action, true);
                xhr.responseType = 'blob';
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                xhr.upload.onprogress = function (e) {
                    if (!e.lengthComputable) return;
                    const percent = Math.max(8, Math.min(48, Math.round((e.loaded / e.total) * 48)));
                    processingValue = Math.max(processingValue, percent);
                    updateExamProgress(percent, 'Veri yükleniyor...', 'Öğrenci listesi ve sınav bilgileri sunucuya gönderiliyor.');
                };

                xhr.onloadstart = function () {
                    updateExamProgress(10, 'Yükleme başladı', 'Dosyalar gönderiliyor.');
                };

                xhr.onreadystatechange = function () {
                    if (xhr.readyState === XMLHttpRequest.HEADERS_RECEIVED) {
                        processingValue = Math.max(processingValue, 55);
                        updateExamProgress(processingValue, 'PDF belgeleri hazırlanıyor...', 'Salon yerleşimi oluşturuluyor ve paket hazırlanıyor.');
                    }
                };

                processingTimer = window.setInterval(() => {
                    if (xhr.readyState >= XMLHttpRequest.DONE) return;
                    const step = processingValue < 55 ? 1 : 0.8;
                    const cap = 96;
                    processingValue = Math.min(cap, processingValue + step);
                    const phase = processingValue < 55
                        ? 'Veri yükleniyor...'
                        : (processingValue < 90 ? 'PDF belgeleri hazırlanıyor...' : 'Paket son kontrolleri yapılıyor...');
                    updateExamProgress(Math.round(processingValue), phase, 'İşlem devam ediyor, lütfen bekleyin.');
                }, 220);

                xhr.onprogress = function (e) {
                    if (!e.lengthComputable || xhr.readyState < XMLHttpRequest.LOADING) return;
                    const percent = Math.max(processingValue, Math.min(98, 68 + Math.round((e.loaded / e.total) * 30)));
                    updateExamProgress(percent, 'Paket indiriliyor...', 'Zip dosyası tarayıcıya aktarılıyor.');
                };

                xhr.onload = function () {
                    if (processingTimer) window.clearInterval(processingTimer);

                    if (xhr.status >= 200 && xhr.status < 300) {
                        updateExamProgress(100, 'Tamamlandı', 'Sınav giriş belgesi paketi indiriliyor.');
                        const filename = extractDownloadFilename(xhr);
                        const blobUrl = window.URL.createObjectURL(xhr.response);
                        const link = document.createElement('a');
                        link.href = blobUrl;
                        link.download = filename;
                        document.body.appendChild(link);
                        link.click();
                        link.remove();
                        window.URL.revokeObjectURL(blobUrl);
                        hideExamProgress();
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function () {
                        let message = 'Paket oluşturulamadı.';
                        try {
                            const parsed = JSON.parse(reader.result);
                            message = parsed.message || message;
                        } catch (error) {}

                        updateExamProgress(100, 'Hata', message);
                        hideExamProgress(2500);
                        alert(message);
                    };
                    reader.readAsText(xhr.response);
                };

                xhr.onerror = function () {
                    if (processingTimer) window.clearInterval(processingTimer);
                    updateExamProgress(100, 'Bağlantı Hatası', 'İşlem sırasında ağ hatası oluştu.');
                    hideExamProgress(2500);
                    alert('İşlem sırasında bağlantı hatası oluştu.');
                };

                xhr.onloadend = function () {
                    if (submitButton) submitButton.disabled = false;
                    if (processingTimer) window.clearInterval(processingTimer);
                };

                xhr.send(formData);
            });
        });
    </script>
</x-app-layout>



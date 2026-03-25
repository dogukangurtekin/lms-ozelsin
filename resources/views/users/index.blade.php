<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Kayıt Yönetimi</h2></x-slot>

    <div class="space-y-5" x-data="{ tab: '{{ old('tab', request('tab', 'ozet')) }}' }">
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm text-rose-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
            <div class="flex flex-wrap gap-2">
                <button @click="tab='ozet'" :class="tab==='ozet' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600'" class="px-4 py-2 rounded-xl text-sm font-semibold">Özet</button>
                <button @click="tab='ogrenciler'" :class="tab==='ogrenciler' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600'" class="px-4 py-2 rounded-xl text-sm font-semibold">Öğrenciler</button>
                <button @click="tab='ogretmenler'" :class="tab==='ogretmenler' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600'" class="px-4 py-2 rounded-xl text-sm font-semibold">Öğretmenler</button>
                <button @click="tab='siniflar'" :class="tab==='siniflar' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600'" class="px-4 py-2 rounded-xl text-sm font-semibold">Sınıflar</button>
                <button @click="tab='raporlar'" :class="tab==='raporlar' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600'" class="px-4 py-2 rounded-xl text-sm font-semibold">Raporlar</button>
            </div>
        </section>

        <section x-show="tab==='ozet'" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                <div class="rounded-2xl p-4 shadow-sm border border-indigo-200" style="background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);">
                    <p class="text-xs text-indigo-700 font-semibold">TOPLAM ÖĞRENCİ</p><p class="text-3xl font-bold mt-1 text-indigo-950">{{ $summary['total_students'] }}</p>
                </div>
                <div class="rounded-2xl p-4 shadow-sm border border-emerald-200" style="background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);">
                    <p class="text-xs text-emerald-700 font-semibold">AKTİF ÖĞRENCİ</p><p class="text-3xl font-bold mt-1 text-emerald-950">{{ $summary['active_students'] }}</p>
                </div>
                <div class="rounded-2xl p-4 shadow-sm border border-fuchsia-200" style="background: linear-gradient(135deg, #fae8ff 0%, #f5d0fe 100%);">
                    <p class="text-xs text-fuchsia-700 font-semibold">TOPLAM ÖĞRETMEN</p><p class="text-3xl font-bold mt-1 text-fuchsia-950">{{ $summary['total_teachers'] }}</p>
                </div>
                <div class="rounded-2xl p-4 shadow-sm border border-cyan-200" style="background: linear-gradient(135deg, #cffafe 0%, #a5f3fc 100%);">
                    <p class="text-xs text-cyan-700 font-semibold">TOPLAM SINIF</p><p class="text-3xl font-bold mt-1 text-cyan-950">{{ $summary['total_classes'] }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <h3 class="text-xl font-semibold text-slate-800">Sınıf Bazlı Öğrenci Dağılımı</h3>
                    <p class="text-sm text-slate-400">Seviye bazında öğrenci sayıları</p>
                    <div class="h-64 mt-4"><canvas id="gradeDistributionChart"></canvas></div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4">
                        @foreach($gradeDistribution as $item)
                            <div class="rounded-xl border border-slate-200 p-3">
                                <p class="text-xs text-slate-400">{{ $item['grade'] }}. Sinif</p>
                                <p class="text-2xl font-bold text-slate-800">{{ $item['count'] }} <span class="text-sm text-slate-400">%{{ $item['percent'] }}</span></p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <h3 class="text-xl font-semibold text-slate-800">Öğrenci Durum Dağılımı</h3>
                    <p class="text-sm text-slate-400">Aktif ve pasif öğrenciler</p>
                    <div class="h-64 mt-4"><canvas id="genderChart"></canvas></div>
                </div>
            </div>
        </section>

        <section x-show="tab==='ogrenciler'" style="display:none;" class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <h3 class="font-semibold text-slate-800">Yeni Ogrenci Kaydi</h3>
                <form method="POST" action="{{ route('users.students.store') }}" class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3">
                    @csrf
                    <input type="hidden" name="tab" value="ogrenciler">
                    <input name="name" placeholder="Ad Soyad" class="rounded-lg border-slate-300" required>
                    <input name="email" type="email" placeholder="E-posta" class="rounded-lg border-slate-300" required>
                    <input name="phone" placeholder="Telefon" class="rounded-lg border-slate-300">
                    <input name="password" type="password" placeholder="Sifre" class="rounded-lg border-slate-300" required>
                    <input name="student_number" placeholder="Ogrenci No" class="rounded-lg border-slate-300" required>
                    <input name="birth_date" type="date" class="rounded-lg border-slate-300">
                    <select name="class_id" class="rounded-lg border-slate-300">
                        <option value="">Sinif Sec</option>@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach
                    </select>
                    <select name="is_active" class="rounded-lg border-slate-300"><option value="1">Aktif</option><option value="0">Pasif</option></select>
                    <button class="rounded-lg px-4 py-2 font-semibold" style="background:#2563eb;color:#fff;border:1px solid #1d4ed8;">Kaydet</button>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="font-semibold text-slate-800">Toplu Ogrenci Kayit</h3>
                </div>
                <a href="{{ route('users.templates.download', 'students') }}" class="mt-3 inline-flex items-center rounded-lg text-white px-4 py-2 text-sm font-semibold shadow-sm border" style="background:#16a34a;border-color:#15803d;">Excel Şablonunu İndir (.xls)</a>
                <form method="POST" action="{{ route('users.students.import') }}" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-center gap-3 bulk-upload-form" data-label="Öğrenci toplu yükleme">
                    @csrf
                    <input type="file" name="import_file" accept=".csv,.txt,.xls,.xlsx" class="rounded-lg border-slate-300" required>
                    <button class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm">Toplu Yukle (csv/xls/xlsx/txt)</button>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 overflow-visible mobile-table-wrap">
                <h3 class="font-semibold text-slate-800 mb-3">Ogrenci Listesi</h3>
                <table class="lms-table">
                    <thead><tr><th>Ad Soyad</th><th>Sınıf</th><th>Numara</th><th>E-posta</th><th>Telefon</th><th>Durum</th><th class="text-right">Ayarlar</th></tr></thead>
                    <tbody>
                    @forelse($studentTable as $row)
                        <tr>
                            <td class="font-semibold">{{ $row->name }}</td>
                            <td>{{ $row->class_name ?? '-' }}</td>
                            <td>{{ $row->student_number ?? '-' }}</td>
                            <td>{{ $row->email }}</td>
                            <td>{{ $row->phone ?? '-' }}</td>
                            <td>@if($row->is_active)<span class="px-2 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs">Aktif</span>@else<span class="px-2 py-1 rounded-full bg-rose-100 text-rose-700 text-xs">Pasif</span>@endif</td>
                            <td class="text-right relative">
                                <div class="relative inline-block text-left" x-data="{ open: false, x: 0, y: 0 }">
                                    <button type="button" @click="const r=$el.getBoundingClientRect(); x=r.right-150; y=r.bottom+6; open=!open" class="rounded-lg border border-slate-300 px-3 py-1 text-xs bg-white">Ayarlar</button>
                                    <div x-show="open" @click.outside="open=false" style="display:none;" class="fixed w-36 rounded-lg border border-slate-200 bg-white shadow-lg z-[99999]" :style="`left:${x}px;top:${y}px`">
                                        <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-slate-50">Göster</button>
                                        <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-slate-50">Düzenle</button>
                                        <form method="POST" action="{{ route('users.students.destroy', $row->id) }}" onsubmit="return confirm('Bu öğrenci silinsin mi?')">
                                            @csrf @method('DELETE')
                                            <button class="w-full text-left px-3 py-2 text-sm text-rose-600 hover:bg-rose-50">Sil</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">Kayıt yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section x-show="tab==='ogretmenler'" style="display:none;" class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <h3 class="font-semibold text-slate-800">Yeni Ogretmen Kaydi</h3>
                <form method="POST" action="{{ route('users.teachers.store') }}" class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3">
                    @csrf
                    <input type="hidden" name="tab" value="ogretmenler">
                    <input name="name" placeholder="Ad Soyad" class="rounded-lg border-slate-300" required>
                    <input name="email" type="email" placeholder="E-posta" class="rounded-lg border-slate-300" required>
                    <input name="phone" placeholder="Telefon" class="rounded-lg border-slate-300">
                    <input name="password" type="password" placeholder="Sifre" class="rounded-lg border-slate-300" required>
                    <input name="branch" placeholder="Brans" class="rounded-lg border-slate-300">
                    <select name="is_active" class="rounded-lg border-slate-300"><option value="1">Aktif</option><option value="0">Pasif</option></select>
                    <div class="md:col-span-3 rounded-lg border border-slate-200 p-3 grid grid-cols-2 md:grid-cols-4 gap-2">
                        @foreach($classes as $class)
                            <label class="text-sm text-slate-600"><input type="checkbox" name="class_ids[]" value="{{ $class->id }}" class="rounded border-slate-300"> {{ $class->name }}</label>
                        @endforeach
                    </div>
                    <button class="rounded-lg px-4 py-2 md:col-span-3 font-semibold" style="background:#16a34a;color:#fff;border:1px solid #15803d;">Kaydet</button>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="font-semibold text-slate-800">Toplu Ogretmen Kayit</h3>
                </div>
                <a href="{{ route('users.templates.download', 'teachers') }}" class="mt-3 inline-flex items-center rounded-lg text-white px-4 py-2 text-sm font-semibold shadow-sm border" style="background:#16a34a;border-color:#15803d;">Excel Şablonunu İndir (.xls)</a>
                <form method="POST" action="{{ route('users.teachers.import') }}" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-center gap-3 bulk-upload-form" data-label="Öğretmen toplu yükleme">
                    @csrf
                    <input type="file" name="import_file" accept=".csv,.txt,.xls,.xlsx" class="rounded-lg border-slate-300" required>
                    <button class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm">Toplu Yukle (csv/xls/xlsx/txt)</button>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 overflow-visible mobile-table-wrap">
                <h3 class="font-semibold text-slate-800 mb-3">Ogretmen Listesi</h3>
                <table class="lms-table">
                    <thead><tr><th>Ad Soyad</th><th>Branş</th><th>Sorumlu Sınıflar</th><th>E-posta</th><th>Telefon</th><th>Durum</th><th class="text-right">Ayarlar</th></tr></thead>
                    <tbody>
                    @forelse($teacherTable as $row)
                        <tr>
                            <td class="font-semibold">{{ $row->name }}</td>
                            <td>{{ $row->branch ?? '-' }}</td>
                            <td>{{ $row->class_names ?: '-' }}</td>
                            <td>{{ $row->email }}</td>
                            <td>{{ $row->phone ?? '-' }}</td>
                            <td>@if($row->is_active)<span class="px-2 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs">Aktif</span>@else<span class="px-2 py-1 rounded-full bg-rose-100 text-rose-700 text-xs">Pasif</span>@endif</td>
                            <td class="text-right relative">
                                <div class="relative inline-block text-left" x-data="{ open: false, x: 0, y: 0 }">
                                    <button type="button" @click="const r=$el.getBoundingClientRect(); x=r.right-150; y=r.bottom+6; open=!open" class="rounded-lg border border-slate-300 px-3 py-1 text-xs bg-white">Ayarlar</button>
                                    <div x-show="open" @click.outside="open=false" style="display:none;" class="fixed w-36 rounded-lg border border-slate-200 bg-white shadow-lg z-[99999]" :style="`left:${x}px;top:${y}px`">
                                        <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-slate-50">Göster</button>
                                        <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-slate-50">Düzenle</button>
                                        @if($row->teacher_profile_id)
                                            <form method="POST" action="{{ route('users.teachers.destroy', $row->teacher_profile_id) }}" onsubmit="return confirm('Bu öğretmen silinsin mi?')">
                                                @csrf @method('DELETE')
                                                <button class="w-full text-left px-3 py-2 text-sm text-rose-600 hover:bg-rose-50">Sil</button>
                                            </form>
                                        @else
                                            <button type="button" class="w-full text-left px-3 py-2 text-sm text-slate-400 cursor-not-allowed">Sil (profil yok)</button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">Kayıt yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section x-show="tab==='siniflar'" style="display:none;" class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <h3 class="font-semibold text-slate-800">Sinif Ekle</h3>
                <form method="POST" action="{{ route('users.classes.store') }}" class="mt-3 grid grid-cols-1 md:grid-cols-4 gap-3">
                    @csrf
                    <input name="grade_level" placeholder="Sinif Seviyesi (5,6,7...)" class="rounded-lg border-slate-300" required>
                    <input name="section" placeholder="Sube (A,B,C...)" class="rounded-lg border-slate-300" required>
                    <select name="homeroom_teacher_id" class="rounded-lg border-slate-300">
                        <option value="">Sube Ogretmeni Sec</option>
                        @foreach($teacherUsers as $teacherUser)
                            <option value="{{ $teacherUser->id }}">{{ $teacherUser->name }}</option>
                        @endforeach
                    </select>
                    <input name="description" placeholder="Aciklama" class="rounded-lg border-slate-300">
                    <button class="rounded-lg px-4 py-2 md:col-span-4 font-semibold" style="background:#0f172a;color:#fff;border:1px solid #0f172a;">Sınıf Kaydet</button>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="font-semibold text-slate-800">Toplu Sinif Kayit</h3>
                </div>
                <a href="{{ route('users.templates.download', 'classes') }}" class="mt-3 inline-flex items-center rounded-lg text-white px-4 py-2 text-sm font-semibold shadow-sm border" style="background:#16a34a;border-color:#15803d;">Excel Şablonunu İndir (.xls)</a>
                <form method="POST" action="{{ route('users.classes.import') }}" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-center gap-3 bulk-upload-form" data-label="Sınıf toplu yükleme">
                    @csrf
                    <input type="file" name="import_file" accept=".csv,.txt,.xls,.xlsx" class="rounded-lg border-slate-300" required>
                    <button class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm">Toplu Yukle (csv/xls/xlsx/txt)</button>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 overflow-visible mobile-table-wrap">
                <h3 class="font-semibold text-slate-800 mb-3">Sinif Listesi</h3>
                <table class="lms-table">
                    <thead><tr><th>Sınıf</th><th>Seviye</th><th>Şube</th><th>Şube Öğretmeni</th><th>Açıklama</th><th class="text-right">Ayarlar</th></tr></thead>
                    <tbody>
                    @forelse($classes as $class)
                        <tbody x-data="{ editOpen: false }">
                        <tr>
                            <td class="font-semibold">{{ $class->name }}</td>
                            <td>{{ $class->grade_level }}</td>
                            <td>{{ $class->section ?? '-' }}</td>
                            <td>{{ $class->homeroomTeacher?->name ?? '-' }}</td>
                            <td>{{ $class->description ?? '-' }}</td>
                            <td class="text-right relative">
                                <div class="relative inline-block text-left" x-data="{ open: false, x: 0, y: 0 }">
                                    <button type="button" @click="const r=$el.getBoundingClientRect(); x=r.right-150; y=r.bottom+6; open=!open" class="rounded-lg border border-slate-300 px-3 py-1 text-xs bg-white">Ayarlar</button>
                                    <div x-show="open" @click.outside="open=false" style="display:none;" class="fixed w-36 rounded-lg border border-slate-200 bg-white shadow-lg z-[99999]" :style="`left:${x}px;top:${y}px`">
                                        <button type="button" @click="editOpen=true; open=false" class="w-full text-left px-3 py-2 text-sm hover:bg-slate-50">Düzenle</button>
                                        <form method="POST" action="{{ route('users.classes.destroy', $class->id) }}" onsubmit="return confirm('Bu sınıf silinsin mi?')">
                                            @csrf @method('DELETE')
                                            <button class="w-full text-left px-3 py-2 text-sm text-rose-600 hover:bg-rose-50">Sil</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr x-show="editOpen" style="display:none;" class="bg-slate-50">
                            <td colspan="6">
                                <form method="POST" action="{{ route('users.classes.update', $class->id) }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 p-3">
                                    @csrf @method('PUT')
                                    <div>
                                        <label class="block text-xs text-slate-600 mb-1">Sınıf Adı</label>
                                        <input name="name" value="{{ $class->name }}" class="w-full rounded-lg border-slate-300 text-sm" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-slate-600 mb-1">Şube</label>
                                        <input name="section" value="{{ $class->section }}" class="w-full rounded-lg border-slate-300 text-sm" placeholder="A, B, C">
                                    </div>
                                    <div class="md:col-span-2 flex items-end gap-2">
                                        <button class="rounded-lg bg-blue-600 text-white px-3 py-2 text-sm">Güncelle</button>
                                        <button type="button" @click="editOpen=false" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">Vazgeç</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        </tbody>
                    @empty
                        <tr><td colspan="6">Sınıf kaydı yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section x-show="tab==='raporlar'" style="display:none;" class="rounded-2xl border border-slate-200 bg-white p-5">
            <h3 class="text-lg font-semibold text-slate-900">Raporlar (Temel)</h3>
            <p class="text-sm text-slate-500 mt-1">Bu alan detayli raporlama modulune hazir olarak birakildi.</p>
            <a href="{{ route('reports.index') }}" class="inline-flex mt-4 rounded-lg bg-slate-900 px-4 py-2 text-white text-sm">Rapor Sayfasina Git</a>
        </section>
    </div>

    <div id="uploadProgressBox" class="fixed right-5 bottom-5 z-50 w-80 rounded-xl border border-slate-200 bg-white shadow-lg p-4 hidden mobile-progress-box">
        <p id="uploadProgressLabel" class="text-sm font-semibold text-slate-700">Dosya yükleniyor...</p>
        <div class="mt-2 h-3 rounded-full bg-slate-200 overflow-hidden">
            <div id="uploadProgressBar" class="h-full bg-emerald-600 transition-all" style="width: 0%"></div>
        </div>
        <p id="uploadProgressText" class="mt-2 text-xs text-slate-500">%0</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <script>
        const gradeDistributionCtx = document.getElementById('gradeDistributionChart');
        const statusCtx = document.getElementById('genderChart');

        if (gradeDistributionCtx) {
            new Chart(gradeDistributionCtx, {
                type: 'doughnut',
                data: {
                    labels: @json($gradeDistribution->map(fn($item) => $item['grade'] . '. Sinif')),
                    datasets: [{
                        data: @json($gradeDistribution->pluck('count')),
                        backgroundColor: ['#1677e8', '#35be57', '#f39200', '#ff3b30', '#7c3aed', '#0ea5e9'],
                        borderWidth: 0,
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    cutout: '52%',
                    plugins: { legend: { position: 'bottom', labels: { color: '#334155' } } }
                }
            });
        }

        if (statusCtx) {
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Aktif', 'Pasif'],
                    datasets: [{
                        data: [{{ $genderData['active'] }}, {{ $genderData['passive'] }}],
                        backgroundColor: ['#1d4ed8', '#f43f5e'],
                        borderWidth: 0,
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    cutout: '52%',
                    plugins: { legend: { position: 'bottom', labels: { color: '#334155' } } }
                }
            });
        }

        const progressBox = document.getElementById('uploadProgressBox');
        const progressBar = document.getElementById('uploadProgressBar');
        const progressText = document.getElementById('uploadProgressText');
        const progressLabel = document.getElementById('uploadProgressLabel');

        function setProgress(percent, label) {
            progressBox.classList.remove('hidden');
            progressBar.style.width = `${percent}%`;
            progressText.textContent = `%${percent}`;
            if (label) progressLabel.textContent = label;
        }

        async function convertExcelFileToRows(file) {
            const ext = (file.name.split('.').pop() || '').toLowerCase();
            if (!['xls', 'xlsx'].includes(ext)) return null;
            const buffer = await file.arrayBuffer();
            const workbook = XLSX.read(buffer, { type: 'array' });
            const firstSheetName = workbook.SheetNames[0];
            if (!firstSheetName) return [];
            const sheet = workbook.Sheets[firstSheetName];
            return XLSX.utils.sheet_to_json(sheet, { defval: '' });
        }

        document.querySelectorAll('.bulk-upload-form').forEach((form) => {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                const xhr = new XMLHttpRequest();
                const formData = new FormData(form);
                const label = form.dataset.label || 'Toplu yükleme';
                const submitButton = form.querySelector('button[type="submit"], button:not([type])');
                if (submitButton) submitButton.disabled = true;

                const fileInput = form.querySelector('input[type="file"][name="import_file"]');
                const file = fileInput?.files?.[0];
                if (file) {
                    const parsedRows = await convertExcelFileToRows(file);
                    if (parsedRows) {
                        formData.append('parsed_rows_json', JSON.stringify(parsedRows));
                    }
                }

                xhr.open('POST', form.action, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('Accept', 'application/json');

                xhr.upload.onprogress = function (e) {
                    if (!e.lengthComputable) return;
                    const percent = Math.round((e.loaded / e.total) * 100);
                    setProgress(percent, `${label} devam ediyor...`);
                };

                xhr.onloadstart = function () {
                    setProgress(0, `${label} başlatıldı...`);
                };

                xhr.onerror = function () {
                    setProgress(0, 'Yükleme sırasında hata oluştu.');
                    if (submitButton) submitButton.disabled = false;
                };

                xhr.onload = function () {
                    if (xhr.status >= 200 && xhr.status < 400) {
                        let response = {};
                        try { response = JSON.parse(xhr.responseText || '{}'); } catch (e) {}
                        setProgress(100, response.message || `${label} tamamlandı.`);
                        setTimeout(() => window.location.reload(), 500);
                        return;
                    }

                    let errorMessage = 'Yükleme başarısız. Dosya formatını kontrol edin.';
                    try {
                        const response = JSON.parse(xhr.responseText || '{}');
                        if (response.message) errorMessage = response.message;
                    } catch (e) {}
                    setProgress(0, errorMessage);
                    if (submitButton) submitButton.disabled = false;
                };

                xhr.send(formData);
            });
        });
    </script>
</x-app-layout>



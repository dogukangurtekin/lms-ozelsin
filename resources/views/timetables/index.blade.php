<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Ders Programi Modulu</h2>
    </x-slot>

    <div class="space-y-4" x-data="{ openCell: '', tab: '{{ request('tab', 'ders-secimi') }}' }">
        @if(session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-emerald-700 text-sm">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-rose-700 text-sm">
                <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-3">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                <button type="button" @click="tab='ders-secimi'" :class="tab==='ders-secimi' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700'" class="rounded-lg px-4 py-2 text-sm font-semibold w-full">Ders Secimi</button>
                <button type="button" @click="tab='programlar'" :class="tab==='programlar' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700'" class="rounded-lg px-4 py-2 text-sm font-semibold w-full">Ders Programlari</button>
                <button type="button" @click="tab='toplu'" :class="tab==='toplu' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700'" class="rounded-lg px-4 py-2 text-sm font-semibold w-full">Toplu Kayit</button>
            </div>
        </section>

        <section x-show="tab==='ders-secimi'" x-cloak class="rounded-2xl border border-slate-200 bg-white p-4">
            <h3 class="font-semibold text-slate-800">Program Parametreleri</h3>
            <form method="GET" action="{{ route('timetables.index') }}" class="mt-3 grid grid-cols-1 md:grid-cols-[1fr_220px_180px] gap-3 items-end">
                <div>
                    <label class="text-sm text-slate-600">Gunler</label>
                    <div class="mt-2 grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2">
                        @foreach($dayOptions as $day)
                            <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm min-w-0">
                                <input type="checkbox" name="days[]" value="{{ $day['id'] }}" @checked($selectedDays->contains($day['id']))>
                                <span>{{ $day['name'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="text-sm text-slate-600">Gunluk Ders Saati</label>
                    <input type="number" min="1" max="12" name="period_count" value="{{ $periodCount }}" class="mt-2 h-10 w-full rounded-lg border-slate-300 text-sm">
                </div>
                <button class="h-10 rounded-lg bg-slate-900 text-white px-3 py-2 text-sm whitespace-nowrap">Tabloyu Olustur</button>
            </form>

            <h4 class="font-semibold text-slate-700 mt-5">Ders Saati ve Ogle Arasi Ayarlari</h4>
            <form method="POST" action="{{ route('timetables.settings.update') }}" class="mt-3 grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
                @csrf
                <input type="hidden" name="period_count" value="{{ $periodCount }}">
                <input type="hidden" name="grid_class_id" value="{{ $selectedGridClassId ?? 0 }}">
                @foreach($selectedDays as $d)<input type="hidden" name="days[]" value="{{ $d }}">@endforeach

                <div>
                    <label class="text-xs text-slate-600">Baslangic Saati</label>
                    <input type="time" name="day_start_time" value="{{ substr((string)$settings->day_start_time,0,5) }}" class="mt-1 h-9 w-full rounded-md border-slate-300 text-xs">
                </div>
                <div>
                    <label class="text-xs text-slate-600">Ders Suresi (dk)</label>
                    <input type="number" name="lesson_duration" value="{{ $settings->lesson_duration }}" min="20" max="90" class="mt-1 h-9 w-full rounded-md border-slate-300 text-xs">
                </div>
                <div>
                    <label class="text-xs text-slate-600">Teneffus (dk)</label>
                    <input type="number" name="short_break_duration" value="{{ $settings->short_break_duration }}" min="0" max="30" class="mt-1 h-9 w-full rounded-md border-slate-300 text-xs">
                </div>
                <div>
                    <label class="text-xs text-slate-600">Ogle Arasi (kacinci ders sonrasi)</label>
                    <input type="number" name="lunch_after_period" value="{{ $settings->lunch_after_period }}" min="1" max="12" class="mt-1 h-9 w-full rounded-md border-slate-300 text-xs">
                </div>
                <div>
                    <label class="text-xs text-slate-600">Ogle Arasi Suresi (dk)</label>
                    <input type="number" name="lunch_duration" value="{{ $settings->lunch_duration }}" min="10" max="120" class="mt-1 h-9 w-full rounded-md border-slate-300 text-xs">
                </div>
                <button class="h-9 rounded-lg bg-blue-600 text-white px-3 text-xs font-semibold">Saat Ayarlarini Kaydet</button>
            </form>
        </section>

        <section x-show="tab==='toplu'" x-cloak class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="font-semibold text-slate-800">Toplu Ders Programi Yukleme</h3>
                <a href="{{ route('timetables.template') }}" class="inline-flex items-center rounded-lg text-white px-4 py-2 text-sm font-semibold shadow-sm border" style="background:#16a34a;border-color:#15803d;">Excel Sablonunu Indir (.xls)</a>
            </div>
            <form method="POST" action="{{ route('timetables.import') }}" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-center gap-3 bulk-upload-form" data-label="Ders programi toplu yukleme">
                @csrf
                <input type="hidden" name="period_count" value="{{ $periodCount }}">
                <input type="hidden" name="grid_class_id" value="{{ $selectedGridClassId ?? 0 }}">
                @foreach($selectedDays as $d)<input type="hidden" name="days[]" value="{{ $d }}">@endforeach
                <input type="file" name="import_file" accept=".csv,.txt,.xls,.xlsx" class="rounded-lg border-slate-300" required>
                <button class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm">Toplu Yukle (csv/xls/xlsx/txt)</button>
            </form>
            <p class="mt-2 text-xs text-slate-500">Sablon kolonlari: sinif, gun, ders_saati, ogretmen_e_posta, ders</p>
        </section>

        <section x-show="tab==='ders-secimi'" x-cloak class="rounded-2xl border border-slate-200 bg-white p-4 overflow-hidden">
            <div class="flex flex-wrap items-center gap-3 mb-3">
                <h3 class="font-semibold text-slate-800 mb-1">Siniflara Gore Haftalik Ders Programi</h3>
                <form method="GET" action="{{ route('timetables.index') }}" class="flex flex-wrap items-center gap-2">
                    <input type="hidden" name="period_count" value="{{ $periodCount }}">
                    @foreach($selectedDays as $d)<input type="hidden" name="days[]" value="{{ $d }}">@endforeach
                    <select name="grid_class_id" onchange="this.form.submit()" class="h-10 rounded-lg border-slate-300 text-sm w-full sm:w-auto sm:min-w-[220px]">
                        <option value="0">Sinif seciniz</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" @selected($selectedGridClassId === $class->id)>{{ $class->name }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <p class="text-xs text-slate-500 mb-3">Slot kartini surukleyip bos hucreye birakarak tasiyabilirsiniz.</p>
            @if(($selectedGridClassId ?? 0) === 0)
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    Haftalik ders programini gormek ve duzenlemek icin once bir sinif seciniz.
                </div>
            @endif
            <div class="space-y-6">
                @foreach($classesForGrid as $class)
                    <div class="rounded-xl border border-slate-200 p-3 mobile-table-wrap">
                        <h4 class="font-semibold text-slate-800 mb-2">{{ $class->name }}</h4>
                        <table class="lms-table table-fixed w-full">
                            <thead>
                                <tr>
                                    <th>Ders Saati</th>
                                    @foreach($selectedDays as $day)
                                        <th>{{ $dayOptions->firstWhere('id', $day)['name'] ?? $day }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($periods as $period)
                                    <tr>
                                        <td class="font-semibold whitespace-nowrap">{{ $period }}. Saat</td>
                                        @foreach($selectedDays as $day)
                                            @php $slot = $slotMap[$class->id][$day][$period] ?? null; $cellKey = $class->id.'-'.$day.'-'.$period; @endphp
                                            <td class="align-top drop-target break-words" data-class-id="{{ $class->id }}" data-day="{{ $day }}" data-period="{{ $period }}">
                                                @if($slot)
                                                    <div
                                                        class="rounded-lg border border-slate-200 p-2 bg-slate-50 timetable-slot cursor-move"
                                                        draggable="true"
                                                        data-schedule-id="{{ $slot->id }}"
                                                    >
                                                        <p class="text-sm font-semibold text-slate-800">{{ $slot->lesson?->short_name ?? $slot->lesson?->name ?? $slot->lesson_name }}</p>
                                                        <form method="POST" action="{{ route('timetables.destroy', $slot) }}" class="mt-1">
                                                            @csrf @method('DELETE')
                                                            <input type="hidden" name="period_count" value="{{ $periodCount }}">
                                                            <input type="hidden" name="grid_class_id" value="{{ $selectedGridClassId ?? 0 }}">
                                                            @foreach($selectedDays as $d)<input type="hidden" name="days[]" value="{{ $d }}">@endforeach
                                                            <button class="text-xs text-rose-600">Sil</button>
                                                        </form>
                                                    </div>
                                                @else
                                                    <button type="button" @click="openCell = openCell === '{{ $cellKey }}' ? '' : '{{ $cellKey }}'" class="rounded-md bg-blue-600 text-white px-2 py-1 text-xs">+ Ekle</button>
                                                @endif

                                                <div x-show="openCell === '{{ $cellKey }}'" x-cloak class="mt-2 rounded-lg border border-slate-200 p-2 bg-white">
                                                    <form method="POST" action="{{ route('timetables.store') }}" class="space-y-2 slot-create-form min-w-0">
                                                        @csrf
                                                        <input type="hidden" name="class_id" value="{{ $class->id }}">
                                                        <input type="hidden" name="day_of_week" value="{{ $day }}">
                                                        <input type="hidden" name="period_no" value="{{ $period }}">
                                                        <input type="hidden" name="period_count" value="{{ $periodCount }}">
                                                        <input type="hidden" name="grid_class_id" value="{{ $selectedGridClassId ?? 0 }}">
                                                        @foreach($selectedDays as $d)<input type="hidden" name="days[]" value="{{ $d }}">@endforeach

                                                        <select name="teacher_id" class="w-full h-8 rounded-md border-slate-300 text-[11px]" required>
                                                            <option value="">Ogretmen sec</option>
                                                            @foreach($teachers as $teacher)
                                                                <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <select name="lesson_id" class="w-full h-8 rounded-md border-slate-300 text-[11px]" required>
                                                            <option value="">Ders sec</option>
                                                            @foreach($lessons as $lesson)
                                                                <option value="{{ $lesson->id }}">{{ ($lesson->short_name ? $lesson->short_name.' - ' : '') . $lesson->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <div class="flex justify-end gap-2">
                                                            <button type="button" @click="openCell=''" class="rounded bg-slate-100 px-2 py-1 text-[11px]">Iptal</button>
                                                            <button class="rounded px-2 py-1 text-[11px] font-semibold" style="background:#16a34a;color:#ffffff;border:1px solid #15803d;">Kaydet</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        </section>

        <section x-show="tab==='programlar'" x-cloak class="rounded-2xl border border-slate-200 bg-white p-4">
            <h3 class="font-semibold text-slate-800">Programlari Goster</h3>
            <form method="GET" action="{{ route('timetables.index') }}" class="mt-3 grid grid-cols-1 md:grid-cols-4 gap-3">
                <input type="hidden" name="period_count" value="{{ $periodCount }}">
                <input type="hidden" name="tab" value="programlar">
                @foreach($selectedDays as $d)<input type="hidden" name="days[]" value="{{ $d }}">@endforeach

                <select name="teacher_view_id" class="rounded-lg border-slate-300" {{ $currentUser->hasRole('teacher') ? 'disabled' : '' }}>
                    <option value="">Ogretmen programi sec</option>
                    @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}" @selected($selectedTeacherId === $teacher->id || ($currentUser->hasRole('teacher') && $currentUser->id === $teacher->id))>{{ $teacher->name }}</option>
                    @endforeach
                </select>
                @if($currentUser->hasRole('teacher'))
                    <input type="hidden" name="teacher_view_id" value="{{ $currentUser->id }}">
                @endif

                <select name="class_view_id" class="rounded-lg border-slate-300">
                    <option value="">Sinif programi sec</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" @selected($selectedClassId === $class->id)>{{ $class->name }}</option>
                    @endforeach
                </select>
                <button class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm">Programlari Listele</button>
            </form>

            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-800">PDF Indirme</p>
                        <p class="text-xs text-slate-500">Ogretmen ve sinif programlarini A4 yatay PDF olarak indirebilirsiniz.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="rounded-lg bg-blue-600 text-white px-4 py-2 text-sm font-semibold disabled:opacity-50"
                            data-pdf-download-button
                            data-url="{{ route('timetables.teacher-pdf', ['teacher_view_id' => $selectedTeacherId, 'period_count' => $periodCount, 'days' => $selectedDays->values()->all()]) }}"
                            data-filename="ogretmen-ders-programi.pdf"
                            @disabled($selectedTeacherId <= 0)
                        >
                            Ogretmen PDF Indir
                        </button>
                        <button
                            type="button"
                            class="rounded-lg bg-emerald-600 text-white px-4 py-2 text-sm font-semibold disabled:opacity-50"
                            data-pdf-download-button
                            data-url="{{ route('timetables.class-pdf', ['class_view_id' => $selectedClassId, 'period_count' => $periodCount, 'days' => $selectedDays->values()->all()]) }}"
                            data-filename="sinif-ders-programi.pdf"
                            @disabled($selectedClassId <= 0)
                        >
                            Sinif PDF Indir
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-4 space-y-4">
                <div class="rounded-xl border border-slate-200 p-3 overflow-x-auto">
                    <h4 class="font-semibold text-slate-800 mb-2">Ogretmen Programi (Tablo)</h4>
                    @if($selectedTeacherId > 0)
                        <table class="min-w-full text-xs border border-slate-300">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="border border-slate-300 p-2 min-w-[110px]">Gun</th>
                                    @foreach($timeline as $col)
                                        <th class="border border-slate-300 p-2 text-center min-w-[96px] {{ $col['type'] === 'lunch' ? 'bg-amber-50' : '' }}">
                                            <div class="font-semibold">{{ $col['title'] }}</div>
                                            <div>{{ substr($col['start'],0,5) }}</div>
                                            <div>{{ substr($col['end'],0,5) }}</div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($selectedDays as $day)
                                    <tr>
                                        <td class="border border-slate-300 p-2 font-semibold">{{ $dayOptions->firstWhere('id', $day)['name'] ?? $day }}</td>
                                        @foreach($timeline as $col)
                                            @if($col['type'] === 'lunch')
                                                <td class="border border-slate-300 p-2 text-center bg-amber-50 font-semibold">OGLE ARASI</td>
                                            @else
                                                @php $slot = $teacherProgramMap[$day][$col['period_no']] ?? null; @endphp
                                                <td class="border border-slate-300 p-2 text-center align-top">
                                                    @if($slot)
                                                        <div class="font-semibold">{{ $slot->lesson?->short_name ?? $slot->lesson?->name ?? $slot->lesson_name }}</div>
                                                    @endif
                                                </td>
                                            @endif
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-slate-500 text-sm">Ogretmen secilince tablo gorunur.</p>
                    @endif
                </div>
                <div class="rounded-xl border border-slate-200 p-3 overflow-x-auto">
                    <h4 class="font-semibold text-slate-800 mb-2">Sinif Programi (Tablo)</h4>
                    @if($selectedClassId > 0)
                        <table class="min-w-full text-xs border border-slate-300">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="border border-slate-300 p-2 min-w-[110px]">Gun</th>
                                    @foreach($timeline as $col)
                                        <th class="border border-slate-300 p-2 text-center min-w-[96px] {{ $col['type'] === 'lunch' ? 'bg-amber-50' : '' }}">
                                            <div class="font-semibold">{{ $col['title'] }}</div>
                                            <div>{{ substr($col['start'],0,5) }}</div>
                                            <div>{{ substr($col['end'],0,5) }}</div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($selectedDays as $day)
                                    <tr>
                                        <td class="border border-slate-300 p-2 font-semibold">{{ $dayOptions->firstWhere('id', $day)['name'] ?? $day }}</td>
                                        @foreach($timeline as $col)
                                            @if($col['type'] === 'lunch')
                                                <td class="border border-slate-300 p-2 text-center bg-amber-50 font-semibold">OGLE ARASI</td>
                                            @else
                                                @php $slot = $classProgramMap[$day][$col['period_no']] ?? null; @endphp
                                                <td class="border border-slate-300 p-2 text-center align-top">
                                                    @if($slot)
                                                        <div class="font-semibold">{{ $slot->lesson?->short_name ?? $slot->lesson?->name ?? $slot->lesson_name }}</div>
                                                    @endif
                                                </td>
                                            @endif
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-slate-500 text-sm">Sinif secilince tablo gorunur.</p>
                    @endif
                </div>
            </div>
        </section>
    </div>

    <div id="uploadProgressBox" class="fixed right-5 bottom-5 z-50 w-80 rounded-xl border border-slate-200 bg-white shadow-lg p-4 hidden mobile-progress-box">
        <p id="uploadProgressLabel" class="text-sm font-semibold text-slate-700">Dosya yukleniyor...</p>
        <div class="mt-2 h-3 rounded-full bg-slate-200 overflow-hidden">
            <div id="uploadProgressBar" class="h-full bg-emerald-600 transition-all" style="width: 0%"></div>
        </div>
        <p id="uploadProgressText" class="mt-2 text-xs text-slate-500">%0</p>
    </div>

    <div id="pdfProgressBox" class="fixed right-5 bottom-28 z-50 w-80 rounded-xl border border-slate-200 bg-white shadow-lg p-4 hidden mobile-progress-box">
        <p id="pdfProgressLabel" class="text-sm font-semibold text-slate-700">PDF hazirlaniyor...</p>
        <div class="mt-2 h-3 rounded-full bg-slate-200 overflow-hidden">
            <div id="pdfProgressBar" class="h-full bg-blue-600 transition-all" style="width: 0%"></div>
        </div>
        <p id="pdfProgressText" class="mt-2 text-xs text-slate-500">%0</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <script>
        (function () {
            const token = '{{ csrf_token() }}';
            const moveUrl = '{{ route('timetables.move') }}';
            const periodCount = {{ $periodCount }};
            const selectedDays = @json($selectedDays->values());

            let draggedScheduleId = null;

            document.addEventListener('dragstart', (event) => {
                const slot = event.target.closest('.timetable-slot');
                if (!slot) return;
                draggedScheduleId = slot.dataset.scheduleId;
                event.dataTransfer.effectAllowed = 'move';
                slot.classList.add('opacity-60');
            });

            document.addEventListener('dragend', (event) => {
                const slot = event.target.closest('.timetable-slot');
                if (!slot) return;
                slot.classList.remove('opacity-60');
            });

            document.querySelectorAll('.drop-target').forEach((cell) => {
                cell.addEventListener('dragover', (event) => {
                    if (!draggedScheduleId) return;
                    event.preventDefault();
                    cell.classList.add('ring-2', 'ring-blue-400');
                });

                cell.addEventListener('dragleave', () => {
                    cell.classList.remove('ring-2', 'ring-blue-400');
                });

                cell.addEventListener('drop', async (event) => {
                    event.preventDefault();
                    cell.classList.remove('ring-2', 'ring-blue-400');
                    if (!draggedScheduleId) return;

                    const payload = {
                        source_schedule_id: Number(draggedScheduleId),
                        target_class_id: Number(cell.dataset.classId),
                        target_day_of_week: Number(cell.dataset.day),
                        target_period_no: Number(cell.dataset.period),
                        period_count: periodCount,
                        grid_class_id: {{ (int)($selectedGridClassId ?? 0) }},
                        days: selectedDays,
                    };

                    try {
                        const response = await fetch(moveUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token,
                            },
                            body: JSON.stringify(payload),
                        });

                        const data = await response.json();
                        if (!response.ok || !data.ok) {
                            alert(data.message || 'Slot tasima basarisiz.');
                            return;
                        }
                        window.location.reload();
                    } catch (e) {
                        alert('Slot tasima sirasinda hata olustu.');
                    } finally {
                        draggedScheduleId = null;
                    }
                });
            });

            function buildDeleteFormHtml(scheduleId) {
                const daysInputs = selectedDays.map((d) => `<input type="hidden" name="days[]" value="${d}">`).join('');
                return `
                    <form method="POST" action="{{ url('timetables') }}/${scheduleId}" class="mt-1">
                        <input type="hidden" name="_token" value="${token}">
                        <input type="hidden" name="_method" value="DELETE">
                        <input type="hidden" name="period_count" value="${periodCount}">
                        <input type="hidden" name="grid_class_id" value="{{ (int)($selectedGridClassId ?? 0) }}">
                        ${daysInputs}
                        <button class="text-xs text-rose-600">Sil</button>
                    </form>
                `;
            }

            document.querySelectorAll('.slot-create-form').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const submitButton = form.querySelector('button[type="submit"], button:last-child');
                    if (submitButton) submitButton.disabled = true;

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': token,
                            },
                            body: new FormData(form),
                        });

                        const data = await response.json();
                        if (!response.ok || !data.ok) {
                            alert(data.message || 'Kayit basarisiz.');
                            if (submitButton) submitButton.disabled = false;
                            return;
                        }

                        const cell = form.closest('.drop-target');
                        if (!cell) return;

                        cell.innerHTML = `
                            <div class="rounded-lg border border-slate-200 p-2 bg-slate-50 timetable-slot cursor-move" draggable="true" data-schedule-id="${data.slot.id}">
                                <p class="text-sm font-semibold text-slate-800">${data.slot.lesson_name || '-'}</p>
                                ${buildDeleteFormHtml(data.slot.id)}
                            </div>
                        `;
                    } catch (e) {
                        alert('Kayit sirasinda hata olustu.');
                        if (submitButton) submitButton.disabled = false;
                    }
                });
            });

            const progressBox = document.getElementById('uploadProgressBox');
            const progressBar = document.getElementById('uploadProgressBar');
            const progressText = document.getElementById('uploadProgressText');
            const progressLabel = document.getElementById('uploadProgressLabel');
            const pdfProgressBox = document.getElementById('pdfProgressBox');
            const pdfProgressBar = document.getElementById('pdfProgressBar');
            const pdfProgressText = document.getElementById('pdfProgressText');
            const pdfProgressLabel = document.getElementById('pdfProgressLabel');

            function setProgress(percent, label) {
                progressBox.classList.remove('hidden');
                progressBar.style.width = `${percent}%`;
                progressText.textContent = `%${percent}`;
                if (label) progressLabel.textContent = label;
            }

            function setPdfProgress(percent, label) {
                pdfProgressBox.classList.remove('hidden');
                pdfProgressBar.style.width = `${percent}%`;
                pdfProgressText.textContent = `%${percent}`;
                if (label) pdfProgressLabel.textContent = label;
            }

            async function convertExcelFileToRows(file) {
                const ext = (file.name.split('.').pop() || '').toLowerCase();
                if (!['xls', 'xlsx'].includes(ext)) return null;
                const buffer = await file.arrayBuffer();
                const workbook = XLSX.read(buffer, { type: 'array' });
                const sheet = workbook.Sheets[workbook.SheetNames[0]];
                return sheet ? XLSX.utils.sheet_to_json(sheet, { defval: '' }) : [];
            }

            document.querySelectorAll('.bulk-upload-form').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const xhr = new XMLHttpRequest();
                    const formData = new FormData(form);
                    const submitButton = form.querySelector('button');
                    const label = form.dataset.label || 'Toplu yukleme';
                    if (submitButton) submitButton.disabled = true;

                    const file = form.querySelector('input[type="file"]')?.files?.[0];
                    if (file) {
                        const parsedRows = await convertExcelFileToRows(file);
                        if (parsedRows) formData.append('parsed_rows_json', JSON.stringify(parsedRows));
                    }

                    xhr.open('POST', form.action, true);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.setRequestHeader('Accept', 'application/json');

                    xhr.upload.onprogress = function (e) {
                        if (!e.lengthComputable) return;
                        setProgress(Math.round((e.loaded / e.total) * 100), `${label} devam ediyor...`);
                    };
                    xhr.onloadstart = function () { setProgress(0, `${label} baslatildi...`); };
                    xhr.onerror = function () { setProgress(0, 'Yukleme sirasinda hata olustu.'); if (submitButton) submitButton.disabled = false; };
                    xhr.onload = function () {
                        if (xhr.status >= 200 && xhr.status < 400) {
                            let response = {};
                            try { response = JSON.parse(xhr.responseText || '{}'); } catch (e) {}
                            setProgress(100, response.message || `${label} tamamlandi.`);
                            setTimeout(() => window.location.reload(), 600);
                            return;
                        }
                        let message = 'Yukleme basarisiz.';
                        try {
                            const response = JSON.parse(xhr.responseText || '{}');
                            if (response.message) message = response.message;
                        } catch (e) {}
                        setProgress(0, message);
                        if (submitButton) submitButton.disabled = false;
                    };

                    xhr.send(formData);
                });
            });

            document.querySelectorAll('[data-pdf-download-button]').forEach((button) => {
                button.addEventListener('click', async () => {
                    const url = button.dataset.url;
                    const filename = button.dataset.filename || 'ders-programi.pdf';
                    if (!url || button.disabled) return;

                    button.disabled = true;
                    let fakePercent = 0;
                    setPdfProgress(3, 'PDF hazirlaniyor...');

                    const timer = window.setInterval(() => {
                        fakePercent = Math.min(fakePercent + 7, 90);
                        setPdfProgress(fakePercent, 'PDF hazirlaniyor...');
                    }, 250);

                    try {
                        const response = await fetch(url, {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (!response.ok) {
                            throw new Error('PDF olusturulamadi.');
                        }

                        const blob = await response.blob();
                        const downloadUrl = window.URL.createObjectURL(blob);
                        const anchor = document.createElement('a');
                        anchor.href = downloadUrl;
                        anchor.download = filename;
                        document.body.appendChild(anchor);
                        setPdfProgress(100, 'PDF indiriliyor...');
                        anchor.click();
                        anchor.remove();
                        window.URL.revokeObjectURL(downloadUrl);
                    } catch (error) {
                        alert('PDF indirme sirasinda hata olustu.');
                        setPdfProgress(0, 'PDF olusturulamadi.');
                    } finally {
                        window.clearInterval(timer);
                        window.setTimeout(() => {
                            pdfProgressBox.classList.add('hidden');
                        }, 1200);
                        button.disabled = false;
                    }
                });
            });
        })();
    </script>
</x-app-layout>

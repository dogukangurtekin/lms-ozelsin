<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Ders Ekleme Modulu</h2></x-slot>

    <div class="space-y-4">
        @if(session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-emerald-700 text-sm">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-rose-700 text-sm">
                <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-4">
            <h3 class="font-semibold text-slate-800">Yeni Ders Ekle</h3>
            <form method="POST" action="{{ route('lessons.store') }}" class="mt-3 grid grid-cols-1 md:grid-cols-5 gap-3">
                @csrf
                <input name="name" placeholder="Ders adi" class="rounded-lg border-slate-300" required>
                <input name="short_name" placeholder="Kisa ders adi (ornek: MAT)" class="rounded-lg border-slate-300">
                <input name="code" placeholder="Ders kodu (opsiyonel)" class="rounded-lg border-slate-300">
                <input name="description" placeholder="Aciklama" class="rounded-lg border-slate-300">
                <select name="is_active" class="rounded-lg border-slate-300"><option value="1">Aktif</option><option value="0">Pasif</option></select>
                <button class="rounded-lg bg-slate-900 text-white px-4 py-2 md:col-span-5">Dersi Kaydet</button>
            </form>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <h3 class="font-semibold text-slate-800">Toplu Ders Kaydi</h3>
                <a href="{{ route('lessons.template') }}" class="inline-flex items-center justify-center rounded-lg text-white px-4 py-2 text-sm font-semibold shadow-sm border w-full sm:w-auto" style="background:#16a34a;border-color:#15803d;">Excel Sablonunu Indir (.xls)</a>
            </div>
            <form method="POST" action="{{ route('lessons.import') }}" enctype="multipart/form-data" class="mt-3 grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-3 items-center bulk-upload-form" data-label="Ders toplu yukleme">
                @csrf
                <input type="file" name="import_file" accept=".csv,.txt,.xls,.xlsx" class="rounded-lg border-slate-300" required>
                <button class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm w-full sm:w-auto">Toplu Yukle (csv/xls/xlsx/txt)</button>
            </form>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4">
            <h3 class="font-semibold text-slate-800 mb-3">Dersler ve Ogretmen Eslestirme</h3>
            <div class="space-y-2 md:hidden">
                @forelse($lessons as $lesson)
                    <article class="rounded-xl border border-slate-200 p-3">
                        <p class="font-semibold text-slate-800">{{ $lesson->name }}</p>
                        <p class="text-xs text-slate-500 mt-1">Kisa Ad: {{ $lesson->short_name ?? '-' }}</p>
                        <p class="text-xs text-slate-500 mt-1">Kod: {{ $lesson->code ?? '-' }}</p>
                        <p class="text-xs text-slate-500 mt-1">Ogretmenler: {{ $lesson->teachers->pluck('name')->join(', ') ?: '-' }}</p>
                        <details class="mt-3">
                            <summary class="inline-flex cursor-pointer rounded bg-blue-600 text-white px-3 py-2 text-xs">Düzenle</summary>
                            <form method="POST" action="{{ route('lessons.update', $lesson) }}" class="mt-2 grid grid-cols-1 gap-2">
                                @csrf
                                @method('PUT')
                                <input name="name" value="{{ $lesson->name }}" class="rounded-lg border-slate-300 text-sm" required>
                                <input name="short_name" value="{{ $lesson->short_name }}" class="rounded-lg border-slate-300 text-sm" placeholder="Kisa ders adi">
                                <input name="code" value="{{ $lesson->code }}" class="rounded-lg border-slate-300 text-sm" placeholder="Kod">
                                <input name="description" value="{{ $lesson->description }}" class="rounded-lg border-slate-300 text-sm" placeholder="Aciklama">
                                <select name="is_active" class="rounded-lg border-slate-300 text-sm">
                                    <option value="1" @selected((int)$lesson->is_active===1)>Aktif</option>
                                    <option value="0" @selected((int)$lesson->is_active===0)>Pasif</option>
                                </select>
                                <button class="rounded bg-blue-600 text-white px-3 py-2 text-xs">Guncelle</button>
                            </form>
                        </details>
                        <form method="POST" action="{{ route('lessons.assign-teacher', $lesson) }}" class="mt-3 grid grid-cols-1 gap-2">
                            @csrf
                            <select name="teacher_id" class="rounded-lg border-slate-300 text-sm">
                                @foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->name }}</option>@endforeach
                            </select>
                            <button class="rounded bg-blue-600 text-white px-3 py-2 text-xs">Eslestir</button>
                        </form>
                        <form method="POST" action="{{ route('lessons.destroy', $lesson) }}" onsubmit="return confirm('Bu ders silinsin mi?')" class="mt-2">
                            @csrf @method('DELETE')
                            <button class="rounded px-3 py-2 text-xs font-semibold" style="background-color:#e11d48;color:#ffffff;border:1px solid #be123c;">Sil</button>
                        </form>
                    </article>
                @empty
                    <p class="text-sm text-slate-500">Ders kaydi yok.</p>
                @endforelse
            </div>

            <div class="mobile-table-wrap hidden md:block">
            <table class="lms-table">
                <thead><tr><th>Ders</th><th>Kisa Ad</th><th>Kod</th><th>Ogretmenler</th><th>Düzenle</th><th>Eslestir</th><th>Sil</th></tr></thead>
                <tbody>
                @forelse($lessons as $lesson)
                    <tr>
                        <td class="font-semibold">{{ $lesson->name }}</td>
                        <td>{{ $lesson->short_name ?? '-' }}</td>
                        <td>{{ $lesson->code ?? '-' }}</td>
                        <td>{{ $lesson->teachers->pluck('name')->join(', ') ?: '-' }}</td>
                        <td>
                            <details>
                                <summary class="inline-flex cursor-pointer rounded bg-blue-600 text-white px-2 py-1 text-xs">Düzenle</summary>
                                <form method="POST" action="{{ route('lessons.update', $lesson) }}" class="mt-2 grid grid-cols-1 gap-2 min-w-[220px]">
                                    @csrf
                                    @method('PUT')
                                    <input name="name" value="{{ $lesson->name }}" class="rounded-lg border-slate-300 text-xs" required>
                                    <input name="short_name" value="{{ $lesson->short_name }}" class="rounded-lg border-slate-300 text-xs" placeholder="Kisa ad">
                                    <input name="code" value="{{ $lesson->code }}" class="rounded-lg border-slate-300 text-xs" placeholder="Kod">
                                    <input name="description" value="{{ $lesson->description }}" class="rounded-lg border-slate-300 text-xs" placeholder="Aciklama">
                                    <select name="is_active" class="rounded-lg border-slate-300 text-xs">
                                        <option value="1" @selected((int)$lesson->is_active===1)>Aktif</option>
                                        <option value="0" @selected((int)$lesson->is_active===0)>Pasif</option>
                                    </select>
                                    <button class="rounded bg-blue-600 text-white px-2 py-1 text-xs">Guncelle</button>
                                </form>
                            </details>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('lessons.assign-teacher', $lesson) }}" class="flex gap-2">
                                @csrf
                                <select name="teacher_id" class="rounded-lg border-slate-300 text-sm">
                                    @foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->name }}</option>@endforeach
                                </select>
                                <button class="rounded bg-blue-600 text-white px-2 text-xs">Eslestir</button>
                            </form>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('lessons.destroy', $lesson) }}" onsubmit="return confirm('Bu ders silinsin mi?')">
                                @csrf @method('DELETE')
                                <button
                                    class="rounded px-2 py-1 text-xs font-semibold"
                                    style="background-color:#e11d48;color:#ffffff;border:1px solid #be123c;"
                                >Sil</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">Ders kaydi yok.</td></tr>
                @endforelse
                </tbody>
            </table>
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

    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <script>
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
                        setTimeout(() => window.location.reload(), 500);
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
    </script>
</x-app-layout>

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
            <form method="POST" action="{{ route('lessons.store') }}" class="mt-3 grid grid-cols-1 md:grid-cols-4 gap-3">
                @csrf
                <input name="name" placeholder="Ders adi" class="rounded-lg border-slate-300" required>
                <input name="code" placeholder="Ders kodu (opsiyonel)" class="rounded-lg border-slate-300">
                <input name="description" placeholder="Aciklama" class="rounded-lg border-slate-300">
                <select name="is_active" class="rounded-lg border-slate-300"><option value="1">Aktif</option><option value="0">Pasif</option></select>
                <button class="rounded-lg bg-slate-900 text-white px-4 py-2 md:col-span-4">Dersi Kaydet</button>
            </form>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="font-semibold text-slate-800">Toplu Ders Kaydı</h3>
                <a href="{{ route('lessons.template') }}" class="inline-flex items-center rounded-lg text-white px-4 py-2 text-sm font-semibold shadow-sm border" style="background:#16a34a;border-color:#15803d;">Excel Şablonunu İndir (.xls)</a>
            </div>
            <form method="POST" action="{{ route('lessons.import') }}" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-center gap-3 bulk-upload-form" data-label="Ders toplu yükleme">
                @csrf
                <input type="file" name="import_file" accept=".csv,.txt,.xls,.xlsx" class="rounded-lg border-slate-300" required>
                <button class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm">Toplu Yükle (csv/xls/xlsx/txt)</button>
            </form>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 overflow-x-auto">
            <h3 class="font-semibold text-slate-800 mb-3">Dersler ve Ogretmen Eslestirme</h3>
            <table class="lms-table">
                <thead><tr><th>Ders</th><th>Kod</th><th>Ogretmenler</th><th>Eslestir</th><th>Sil</th></tr></thead>
                <tbody>
                @forelse($lessons as $lesson)
                    <tr>
                        <td class="font-semibold">{{ $lesson->name }}</td>
                        <td>{{ $lesson->code ?? '-' }}</td>
                        <td>{{ $lesson->teachers->pluck('name')->join(', ') ?: '-' }}</td>
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
                    <tr><td colspan="5">Ders kaydi yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>
    </div>

    <div id="uploadProgressBox" class="fixed right-5 bottom-5 z-50 w-80 rounded-xl border border-slate-200 bg-white shadow-lg p-4 hidden mobile-progress-box">
        <p id="uploadProgressLabel" class="text-sm font-semibold text-slate-700">Dosya yükleniyor...</p>
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
                const label = form.dataset.label || 'Toplu yükleme';
                if (submitButton) submitButton.disabled = true;

                const file = form.querySelector('input[type=\"file\"]')?.files?.[0];
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
                xhr.onloadstart = function () { setProgress(0, `${label} başlatıldı...`); };
                xhr.onerror = function () { setProgress(0, 'Yükleme sırasında hata oluştu.'); if (submitButton) submitButton.disabled = false; };
                xhr.onload = function () {
                    if (xhr.status >= 200 && xhr.status < 400) {
                        let response = {};
                        try { response = JSON.parse(xhr.responseText || '{}'); } catch (e) {}
                        setProgress(100, response.message || `${label} tamamlandı.`);
                        setTimeout(() => window.location.reload(), 500);
                        return;
                    }
                    let message = 'Yükleme başarısız.';
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

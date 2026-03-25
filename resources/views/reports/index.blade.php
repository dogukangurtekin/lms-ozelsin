<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Raporlar</h2></x-slot>

    <div class="space-y-6">
        <div class="grid md:grid-cols-2 xl:grid-cols-5 gap-4">
            <div class="lms-stat-card"><p>Toplam Ogrenci</p><h3>{{ $students }}</h3></div>
            <div class="lms-stat-card"><p>Toplam Ogretmen</p><h3>{{ $teachers }}</h3></div>
            <div class="lms-stat-card"><p>Toplam Odev</p><h3>{{ $assignments }}</h3></div>
            <div class="lms-stat-card"><p>Teslim</p><h3>{{ $submissions }}</h3></div>
            <div class="lms-stat-card"><p>Tamamlama</p><h3>%{{ $completionRate }}</h3></div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
            <div class="xl:col-span-2 lms-panel">
                <p class="lms-panel-title">Ogrenci Basari Ozeti</p>
                <p class="text-sm text-slate-500 mb-4">Temel ortalama puan dagilimi</p>
                <canvas id="performanceChart" height="120"></canvas>
            </div>

            <div class="lms-panel">
                <p class="lms-panel-title">Gelistirme Notu</p>
                <ul class="mt-3 space-y-2 text-sm text-slate-600 list-disc pl-4">
                    <li>Bu ekran rapor altyapisinin temel surumudur.</li>
                    <li>Bir sonraki asamada ogrenci tekil detay sayfasi eklenecek.</li>
                    <li>PDF/Excel disa aktarim, filtreleme ve tarih araligi eklenecek.</li>
                </ul>
            </div>
        </div>

        <div class="lms-panel overflow-x-auto">
            <div class="flex items-center justify-between gap-3 mb-4">
                <p class="lms-panel-title">Ogrenci Veri Erisim Listesi</p>
                <span class="text-xs text-slate-500">Sistem geneline acik temel liste</span>
            </div>

            <table class="lms-table">
                <thead>
                <tr>
                    <th>Ogrenci</th>
                    <th>Numara</th>
                    <th>Sinif</th>
                    <th>Durum</th>
                    <th>Teslim Sayisi</th>
                    <th>Ortalama Puan</th>
                </tr>
                </thead>
                <tbody>
                @forelse($studentRecords as $record)
                    <tr>
                        <td>
                            <p class="font-medium text-slate-800">{{ $record->name }}</p>
                            <p class="text-xs text-slate-500">{{ $record->email }}</p>
                        </td>
                        <td>{{ $record->student_number ?? '-' }}</td>
                        <td>{{ $record->class_name ?? '-' }}</td>
                        <td>
                            @if($record->is_active)
                                <span class="rounded-full bg-emerald-100 text-emerald-700 text-xs px-2 py-1">Aktif</span>
                            @else
                                <span class="rounded-full bg-rose-100 text-rose-700 text-xs px-2 py-1">Pasif</span>
                            @endif
                        </td>
                        <td>{{ (int) $record->total_submissions }}</td>
                        <td>{{ $record->avg_score ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-slate-500">Ogrenci verisi bulunamadi.</td></tr>
                @endforelse
                </tbody>
            </table>

            <div class="mt-4">{{ $studentRecords->links() }}</div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const labels = @json($performance->pluck('student.name')->map(fn($v) => $v ?? 'Bilinmiyor'));
        const data = @json($performance->pluck('avg_score')->map(fn($v) => round((float) $v, 2)));

        new Chart(document.getElementById('performanceChart'), {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Ortalama Puan',
                    data,
                    borderRadius: 8,
                    backgroundColor: '#2563eb'
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true, max: 100 } }
            }
        });
    </script>
</x-app-layout>

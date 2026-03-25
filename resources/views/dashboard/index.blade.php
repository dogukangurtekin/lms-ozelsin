<x-app-layout>
    <x-slot name="header">LMS Dashboard</x-slot>

    <div class="space-y-6">
        <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-4">
            <div class="lms-stat-card"><p>Kullanicilar</p><h3>{{ $stats['users'] }}</h3></div>
            <div class="lms-stat-card"><p>Kitaplar</p><h3>{{ $stats['books'] }}</h3></div>
            <div class="lms-stat-card"><p>Odevler</p><h3>{{ $stats['assignments'] }}</h3></div>
            <div class="lms-stat-card"><p>Teslimler</p><h3>{{ $stats['submissions'] }}</h3></div>
            <div class="lms-stat-card"><p>Puanlanan</p><h3>{{ $stats['graded_submissions'] }}</h3></div>
            <div class="lms-stat-card"><p>Bugun Gorusme</p><h3>{{ $stats['today_meetings'] }}</h3></div>
        </section>

        <section class="grid lg:grid-cols-3 gap-6">
            <article class="lms-panel lg:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="lms-panel-title">7 Gunluk Teslim Aktivitesi</h3>
                    <span class="text-xs text-slate-500">Anlik ozet</span>
                </div>
                <canvas id="activityChart" height="110"></canvas>
            </article>

            <div class="space-y-6">
                <article class="lms-panel">
                    <h3 class="lms-panel-title mb-3">Son Eklenen Odevler</h3>
                    <div class="overflow-x-auto">
                        <table class="lms-table">
                            <thead><tr><th>Baslik</th><th>Ogretmen</th><th>Son Tarih</th></tr></thead>
                            <tbody>
                            @forelse($recentAssignments as $assignment)
                                <tr>
                                    <td>{{ $assignment->title }}</td>
                                    <td>{{ $assignment->teacher?->name }}</td>
                                    <td>{{ optional($assignment->due_at)->format('d.m.Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3">Kayit bulunamadi.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="lms-panel">
                    <h3 class="lms-panel-title mb-3">Son Yoklamalar</h3>
                    <div class="space-y-2">
                        @forelse($recentAttendance as $attendance)
                            <div class="rounded-lg border border-slate-200 px-3 py-2">
                                <div class="font-medium text-sm">{{ optional($attendance->attendance_date)->format('d.m.Y') }} - {{ $attendance->lesson_name }}</div>
                                <div class="text-xs text-slate-600">Sinif: {{ $attendance->class?->name ?? '-' }} | Ogretmen: {{ $attendance->schedule?->teacher?->name ?? '-' }}</div>
                            </div>
                        @empty
                            <div class="text-sm text-slate-500">Kaydedilmis yoklama yok.</div>
                        @endforelse
                    </div>
                </article>
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const activityLabels = @json($activityLabels);
        const activityData = @json($activityData);

        new Chart(document.getElementById('activityChart'), {
            type: 'line',
            data: {
                labels: activityLabels,
                datasets: [{
                    label: 'Teslim Sayisi',
                    data: activityData,
                    borderColor: '#0f766e',
                    backgroundColor: 'rgba(15,118,110,0.12)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.35,
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
    </script>
</x-app-layout>

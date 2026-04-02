<x-app-layout>
    <x-slot name="header">LMS Dashboard</x-slot>

    <div class="space-y-6">
        <section class="grid grid-cols-2 xl:grid-cols-6 gap-4">
            <div class="lms-stat-card"><p>Kullanicilar</p><h3>{{ $stats['users'] }}</h3></div>
            <div class="lms-stat-card"><p>Kitaplar</p><h3>{{ $stats['books'] }}</h3></div>
            <div class="lms-stat-card"><p>Odevler</p><h3>{{ $stats['assignments'] }}</h3></div>
            <div class="lms-stat-card"><p>Teslimler</p><h3>{{ $stats['submissions'] }}</h3></div>
            <div class="lms-stat-card"><p>Puanlanan</p><h3>{{ $stats['graded_submissions'] }}</h3></div>
            <div class="lms-stat-card"><p>Bugun Gorusme</p><h3>{{ $stats['today_meetings'] }}</h3></div>
        </section>

        <section class="grid lg:grid-cols-3 gap-6">
            <article class="lms-panel lg:col-span-2">
                @if(auth()->user()?->hasRole('teacher'))
                    <div class="mb-5">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="lms-panel-title">Haftalık Ders Programım</h3>
                            <span class="text-xs text-slate-500">Bu hafta</span>
                        </div>
                        @if(($teacherPeriods ?? collect())->isNotEmpty())
                            <div class="overflow-x-auto">
                                <table class="lms-table">
                                    <thead>
                                        <tr>
                                            <th>Saat</th>
                                            @foreach($teacherDays as $dayNo => $dayName)
                                                <th>{{ $dayName }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($teacherPeriods as $periodNo)
                                            <tr>
                                                <td class="font-semibold">
                                                    <div class="text-xs text-slate-500">{{ $teacherPeriodTimeMap[$periodNo]['start'] ?? '--:--' }}-{{ $teacherPeriodTimeMap[$periodNo]['end'] ?? '--:--' }}</div>
                                                    <div class="text-base font-semibold text-slate-800">{{ $periodNo }}. Ders</div>
                                                </td>
                                                @foreach($teacherDays as $dayNo => $dayName)
                                                    @php $slot = $teacherScheduleMap[$periodNo][$dayNo] ?? null; @endphp
                                                    <td>
                                                        @if($slot)
                                                            <div class="font-semibold text-slate-900">{{ $slot->lesson?->short_name ?? $slot->lesson?->name ?? $slot->lesson_name }}</div>
                                                            <div class="text-base font-semibold text-slate-700">{{ $slot->class?->name ?? '-' }}</div>
                                                        @else
                                                            <span class="text-slate-300">-</span>
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500">Bu öğretmen için henüz ders programı kaydı yok.</div>
                        @endif
                    </div>
                @endif

                <div class="flex items-center justify-between mb-4">
                    <h3 class="lms-panel-title">Bu Ay Modül Aktivitesi</h3>
                    <span class="text-xs text-slate-500">{{ now()->translatedFormat('F Y') }}</span>
                </div>
                <canvas id="moduleActivityChart" height="110"></canvas>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                        <p class="text-xs text-slate-500">Bu Ay Ödev</p>
                        <p class="text-lg font-semibold text-slate-800">{{ $monthlyActivity['data'][0] }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                        <p class="text-xs text-slate-500">Bu Ay Teslim</p>
                        <p class="text-lg font-semibold text-slate-800">{{ $monthlyActivity['data'][1] }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                        <p class="text-xs text-slate-500">Bu Ay Görüşme</p>
                        <p class="text-lg font-semibold text-slate-800">{{ $monthlyActivity['data'][2] }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                        <p class="text-xs text-slate-500">Bu Ay Yoklama</p>
                        <p class="text-lg font-semibold text-slate-800">{{ $monthlyActivity['data'][3] }}</p>
                    </div>
                </div>
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

                @if(auth()->user()?->hasRole(['admin','teacher']))
                    <article class="lms-panel">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="lms-panel-title">Bugünkü Görüşmeler</h3>
                            <a href="{{ route('meetings.index') }}" class="text-xs text-slate-600 hover:text-slate-900">Tümünü Gör</a>
                        </div>
                        <div class="space-y-2">
                            @php
                                $meetingStatus = ['scheduled' => 'Planlandı', 'completed' => 'Tamamlandı', 'cancelled' => 'İptal Edildi'];
                            @endphp
                            @forelse($todayMeetings as $meeting)
                                <div class="rounded-lg border border-slate-200 px-3 py-2">
                                    <div class="font-medium text-sm">
                                        {{ optional($meeting->meeting_at)->format('H:i') }} -
                                        {{ $meeting->student?->name ?? '-' }}
                                    </div>
                                    <div class="text-xs text-slate-600">
                                        Veli: {{ $meeting->parentUser?->name ?? '-' }} |
                                        Durum: {{ $meetingStatus[$meeting->status] ?? $meeting->status }}
                                    </div>
                                </div>
                            @empty
                                <div class="text-sm text-slate-500">Bugün için görüşme kaydı yok.</div>
                            @endforelse
                        </div>
                    </article>
                @endif

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
        const moduleActivityLabels = @json($monthlyActivity['labels']);
        const moduleActivityData = @json($monthlyActivity['data']);

        new Chart(document.getElementById('moduleActivityChart'), {
            type: 'bar',
            data: {
                labels: moduleActivityLabels,
                datasets: [{
                    label: 'Kayıt Sayısı',
                    data: moduleActivityData,
                    backgroundColor: ['#2563eb', '#0f766e', '#7c3aed', '#ea580c'],
                    borderColor: ['#1d4ed8', '#115e59', '#6d28d9', '#c2410c'],
                    borderWidth: 1,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    </script>
</x-app-layout>

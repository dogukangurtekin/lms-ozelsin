<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Yoklama Modulu</h2></x-slot>

    <div class="space-y-4" x-data>
        @if(session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-emerald-700 text-sm">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-rose-700 text-sm">
                <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-4">
            <form method="GET" action="{{ route('attendance.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Tarih</label>
                    <input type="date" name="date" value="{{ $date }}" class="w-full rounded-lg border-slate-300">
                </div>
                @if(auth()->user()->hasRole('admin'))
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Ogretmen</label>
                        <select name="teacher_id" class="w-full rounded-lg border-slate-300">
                            <option value="">Hepsi</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected((int)request('teacher_id') === $teacher->id)>{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Sınıf (1. Adım)</label>
                    <select name="class_id" class="w-full rounded-lg border-slate-300">
                        <option value="">Sınıf seçin</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" @selected($selectedClassId === $class->id)>{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm">Programi Getir</button>
            </form>
        </section>

        @if($selectedClassId === 0)
            <section class="rounded-2xl border border-slate-200 bg-white p-4">
                <h3 class="font-semibold text-slate-800">Sınıf Seçimi (1. Adım)</h3>
                <p class="mt-1 text-sm text-slate-500">Önce sınıf seçin, sonra yoklama ekranı açılır.</p>
                <div class="mt-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                    @forelse($classes as $class)
                        <a href="{{ route('attendance.index', ['date' => $date, 'teacher_id' => request('teacher_id'), 'class_id' => $class->id]) }}"
                           class="rounded-xl border border-slate-200 bg-white p-3 hover:border-blue-300 hover:bg-blue-50 transition">
                            <p class="font-semibold text-slate-800">{{ $class->name }}</p>
                            <p class="text-xs text-slate-500 mt-1">Yoklama ekranını aç</p>
                        </a>
                    @empty
                        <p class="text-sm text-slate-500">Bu gün için sınıf bulunamadı.</p>
                    @endforelse
                </div>
            </section>
        @endif

        @if($selectedClassId > 0)
        <section class="rounded-2xl border border-slate-200 bg-white p-4">
            <h3 class="font-semibold text-slate-800">Ogretmen Ders Programi</h3>
            <div class="mt-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                @forelse($schedules as $schedule)
                    @php
                        $scheduleSession = $sessionByScheduleId[$schedule->id] ?? null;
                        $isTaken = $scheduleSession && $scheduleSession->taken_at;
                    @endphp
                    <a href="{{ route('attendance.index', ['date' => $date, 'teacher_id' => request('teacher_id'), 'schedule_id' => $schedule->id]) }}"
                       class="rounded-xl border p-3 {{ $selectedSchedule && $selectedSchedule->id === $schedule->id ? 'border-blue-500 bg-blue-50' : 'border-slate-200 bg-white' }}">
                        <p class="font-semibold text-slate-800">{{ $schedule->lesson?->name ?? $schedule->lesson_name }}</p>
                        <p class="text-sm text-slate-500">{{ $schedule->class->name }} - {{ $schedule->teacher->name }}</p>
                        <p class="text-xs text-slate-400 mt-1">{{ substr($schedule->start_time,0,5) }}{{ $schedule->end_time ? ' - '.substr($schedule->end_time,0,5) : '' }}</p>
                        <p class="mt-2 text-xs font-medium {{ $isTaken ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $isTaken ? 'Yoklama alındı' : 'Kayıt yok' }}
                        </p>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">Seçilen sınıf için bu gün aktif ders programı bulunamadı.</p>
                @endforelse
            </div>
        </section>
        @endif

        @if($selectedSchedule)
            @php
                $isCurrentLesson = false;
                if ($date === now()->toDateString()) {
                    $nowTime = now()->format('H:i');
                    $start = substr((string) $selectedSchedule->start_time, 0, 5);
                    $end = $selectedSchedule->end_time ? substr((string) $selectedSchedule->end_time, 0, 5) : null;
                    $isCurrentLesson = $end ? ($start <= $nowTime && $nowTime <= $end) : ($start <= $nowTime);
                }
            @endphp
            <section class="rounded-2xl border border-slate-200 bg-white p-4" x-data="attendanceState()">
                <div class="flex flex-wrap items-center gap-2">
                    <div>
                        <h3 class="font-semibold text-slate-800">
                            {{ $selectedSchedule->class->name }} - {{ $selectedSchedule->lesson?->name ?? $selectedSchedule->lesson_name }} Yoklamasi
                            @if($isCurrentLesson)
                                <span class="ml-2 inline-flex items-center rounded-full bg-blue-100 text-blue-700 px-2 py-0.5 text-xs font-semibold">Şu anki ders</span>
                            @endif
                        </h3>
                        <p class="text-sm text-slate-500">Varsayilan durum: Geldi. Ogrenci kartina tiklayarak degistirebilirsin.</p>
                        <p class="text-xs mt-1 {{ $session && $session->taken_at ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $session && $session->taken_at ? 'Bu ders için yoklama kaydedildi.' : 'Bu ders için henüz kayıt yok.' }}
                        </p>
                    </div>
                </div>

                <form method="POST" action="{{ route('attendance.take') }}" class="mt-4">
                    @csrf
                    <input type="hidden" name="schedule_id" value="{{ $selectedSchedule->id }}">
                    <input type="hidden" name="attendance_date" value="{{ $date }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                        @foreach($students as $student)
                            @php $status = $statusByStudentId[$student->id] ?? 'present'; @endphp
                            <div class="rounded-xl border p-3 cursor-pointer transition"
                                 x-data="{
                                    status: '{{ $status }}',
                                    nextStatus() {
                                        this.status = this.status === 'present'
                                            ? 'absent'
                                            : (this.status === 'absent'
                                                ? 'excused'
                                                : (this.status === 'excused' ? 'medical' : 'present'));
                                    }
                                 }"
                                 @click="nextStatus()"
                                 :style="status==='present' ? 'border-color:#86efac;background:#f0fdf4;' : (status==='absent' ? 'border-color:#fda4af;background:#fff1f2;' : (status==='excused' ? 'border-color:#fde68a;background:#fffbeb;' : 'border-color:#93c5fd;background:#eff6ff;'))">
                                <div class="flex items-center justify-between">
                                    <p class="font-semibold text-slate-800">{{ $student->user?->name ?? '-' }}</p>
                                    <div class="text-right">
                                        <span class="block text-xs text-slate-400">#{{ $student->student_number ?? '-' }}</span>
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold mt-1"
                                              :style="status==='present' ? 'background:#dcfce7;color:#15803d;' : (status==='absent' ? 'background:#ffe4e6;color:#be123c;' : (status==='excused' ? 'background:#fef3c7;color:#b45309;' : 'background:#dbeafe;color:#1d4ed8;'))"
                                              x-text="status==='present' ? 'Geldi' : (status==='absent' ? 'Gelmedi' : (status==='excused' ? 'Izinli' : 'Raporlu'))">
                                        </span>
                                    </div>
                                </div>
                                <input type="hidden" :value="status" name="statuses[{{ $student->id }}]">
                            </div>
                        @endforeach
                    </div>
                    <button class="mt-4 rounded-lg bg-slate-900 text-white px-4 py-2 text-sm">Yoklamayi Kaydet</button>
                </form>
            </section>
        @endif
    </div>

    <script>
        function attendanceState() {
            return {};
        }
    </script>
</x-app-layout>

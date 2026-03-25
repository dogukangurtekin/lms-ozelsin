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
            <form method="GET" action="{{ route('attendance.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
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
                <button class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm">Programi Getir</button>
            </form>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4">
            <h3 class="font-semibold text-slate-800">Ogretmen Ders Programi</h3>
            <div class="mt-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                @forelse($schedules as $schedule)
                    <a href="{{ route('attendance.index', ['date' => $date, 'teacher_id' => request('teacher_id'), 'schedule_id' => $schedule->id]) }}"
                       class="rounded-xl border p-3 {{ $selectedSchedule && $selectedSchedule->id === $schedule->id ? 'border-blue-500 bg-blue-50' : 'border-slate-200 bg-white' }}">
                        <p class="font-semibold text-slate-800">{{ $schedule->lesson?->name ?? $schedule->lesson_name }}</p>
                        <p class="text-sm text-slate-500">{{ $schedule->class->name }} - {{ $schedule->teacher->name }}</p>
                        <p class="text-xs text-slate-400 mt-1">{{ substr($schedule->start_time,0,5) }}{{ $schedule->end_time ? ' - '.substr($schedule->end_time,0,5) : '' }}</p>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">Bu gun icin aktif ders programi bulunamadi.</p>
                @endforelse
            </div>
        </section>

        @if(auth()->user()->hasRole(['admin','teacher']))
            <section class="rounded-2xl border border-slate-200 bg-white p-4">
                <h3 class="font-semibold text-slate-800">Ders Programina Ekle</h3>
                <form method="POST" action="{{ route('attendance.schedules.store') }}" class="mt-3 grid grid-cols-1 md:grid-cols-6 gap-3">
                    @csrf
                    @if(auth()->user()->hasRole('admin'))
                        <select name="teacher_id" class="rounded-lg border-slate-300" required>
                            <option value="">Ogretmen sec</option>
                            @foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->name }}</option>@endforeach
                        </select>
                    @endif
                    <select name="class_id" class="rounded-lg border-slate-300" required>
                        <option value="">Sinif sec</option>
                        @foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach
                    </select>
                    <select name="lesson_id" class="rounded-lg border-slate-300" required>
                        <option value="">Ders sec</option>
                        @foreach($lessons as $lesson)<option value="{{ $lesson->id }}">{{ $lesson->name }}</option>@endforeach
                    </select>
                    <select name="day_of_week" class="rounded-lg border-slate-300" required>
                        <option value="">Gun sec</option>
                        <option value="1">Pazartesi</option><option value="2">Sali</option><option value="3">Carsamba</option>
                        <option value="4">Persembe</option><option value="5">Cuma</option><option value="6">Cumartesi</option><option value="7">Pazar</option>
                    </select>
                    <input name="start_time" type="time" class="rounded-lg border-slate-300" required>
                    <input name="end_time" type="time" class="rounded-lg border-slate-300">
                    <button class="rounded-lg bg-blue-600 text-white px-4 py-2 md:col-span-6">Ders Programina Kaydet</button>
                </form>
            </section>
        @endif

        @if($selectedSchedule)
            <section class="rounded-2xl border border-slate-200 bg-white p-4" x-data="attendanceState()">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="font-semibold text-slate-800">{{ $selectedSchedule->class->name }} - {{ $selectedSchedule->lesson?->name ?? $selectedSchedule->lesson_name }} Yoklamasi</h3>
                        <p class="text-sm text-slate-500">Varsayilan durum: Geldi. Ogrenciye tiklayarak degistirebilirsin.</p>
                    </div>
                    <button type="button" @click="markClassFull()" class="rounded-lg bg-emerald-600 text-white px-3 py-2 text-sm">Sinif Tam (Hepsi Geldi)</button>
                </div>

                <form method="POST" action="{{ route('attendance.take') }}" class="mt-4">
                    @csrf
                    <input type="hidden" name="schedule_id" value="{{ $selectedSchedule->id }}">
                    <input type="hidden" name="attendance_date" value="{{ $date }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                        @foreach($students as $student)
                            @php $status = $statusByStudentId[$student->id] ?? 'present'; @endphp
                            <div class="rounded-xl border border-slate-200 p-3" x-data="{ status: '{{ $status }}' }">
                                <div class="flex items-center justify-between">
                                    <p class="font-semibold text-slate-800">{{ $student->user?->name ?? '-' }}</p>
                                    <span class="text-xs text-slate-400">#{{ $student->student_number ?? '-' }}</span>
                                </div>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <button type="button" @click="status='present'" :class="status==='present' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600'" class="px-2 py-1 rounded text-xs">Geldi</button>
                                    <button type="button" @click="status='absent'" :class="status==='absent' ? 'bg-rose-600 text-white' : 'bg-slate-100 text-slate-600'" class="px-2 py-1 rounded text-xs">Gelmedi</button>
                                    <button type="button" @click="status='excused'" :class="status==='excused' ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-600'" class="px-2 py-1 rounded text-xs">Izinli</button>
                                    <button type="button" @click="status='medical'" :class="status==='medical' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600'" class="px-2 py-1 rounded text-xs">Raporlu</button>
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
            return {
                markClassFull() {
                    document.querySelectorAll('input[name^="statuses["]').forEach((input) => {
                        input.value = 'present';
                    });
                    document.querySelectorAll('[x-data*=\"status:\"]').forEach((el) => {
                        if (el.__x && el.__x.$data) {
                            el.__x.$data.status = 'present';
                        }
                    });
                }
            };
        }
    </script>
</x-app-layout>

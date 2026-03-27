<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\SchoolClass;
use App\Models\TimetableSetting;
use App\Models\TeacherSchedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TimetableController extends Controller
{
    private const DAY_LABELS = [
        1 => 'Pazartesi',
        2 => 'Sali',
        3 => 'Carsamba',
        4 => 'Persembe',
        5 => 'Cuma',
        6 => 'Cumartesi',
        7 => 'Pazar',
    ];

    public function index(Request $request)
    {
        $hasLessonShortName = \Illuminate\Support\Facades\Schema::hasColumn('lessons', 'short_name');
        $settings = $this->getOrCreateSettings();
        $dayOptions = collect(self::DAY_LABELS)->map(fn ($name, $id) => ['id' => $id, 'name' => $name])->values();

        $selectedDays = collect($request->input('days', [1, 2, 3, 4, 5]))
            ->map(fn ($d) => (int) $d)
            ->filter(fn ($d) => $d >= 1 && $d <= 7)
            ->unique()
            ->sort()
            ->values();

        if ($selectedDays->isEmpty()) {
            $selectedDays = collect([1, 2, 3, 4, 5]);
        }

        $periodCount = max(1, min(12, (int) $request->input('period_count', 9)));
        $periods = collect(range(1, $periodCount));

        $classes = SchoolClass::query()
            ->orderBy('grade_level')
            ->orderBy('section')
            ->orderBy('name')
            ->get(['id', 'name', 'grade_level', 'section']);

        $selectedGridClassId = (int) $request->input('grid_class_id', 0);
        $classesForGrid = $selectedGridClassId > 0
            ? $classes->where('id', $selectedGridClassId)->values()
            : collect();

        $teachers = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'teacher'))
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        $lessonSelect = $hasLessonShortName ? ['id', 'name', 'short_name'] : ['id', 'name'];
        $lessons = Lesson::query()->where('is_active', true)->orderBy('name')->get($lessonSelect);

        $slots = TeacherSchedule::query()
            ->with(['teacher:id,name', 'lesson:id,name'])
            ->where('is_active', true)
            ->whereIn('day_of_week', $selectedDays->all())
            ->whereNotNull('period_no')
            ->where('period_no', '<=', $periodCount)
            ->when($selectedGridClassId > 0, fn ($q) => $q->where('class_id', $selectedGridClassId))
            ->get();

        $slotMap = [];
        foreach ($slots as $slot) {
            $slotMap[$slot->class_id][$slot->day_of_week][$slot->period_no] = $slot;
        }

        $currentUser = $request->user();
        $selectedTeacherId = $currentUser->hasRole('teacher')
            ? $currentUser->id
            : (int) $request->input('teacher_view_id', 0);

        $selectedClassId = (int) $request->input('class_view_id', 0);

        $teacherProgram = collect();
        if ($selectedTeacherId > 0) {
            $teacherProgram = TeacherSchedule::query()
                ->with(['class:id,name', 'lesson:id,name'])
                ->where('teacher_id', $selectedTeacherId)
                ->where('is_active', true)
                ->whereIn('day_of_week', $selectedDays->all())
                ->whereNotNull('period_no')
                ->where('period_no', '<=', $periodCount)
                ->orderBy('day_of_week')
                ->orderBy('period_no')
                ->get();
        }

        $classProgram = collect();
        if ($selectedClassId > 0) {
            $classProgram = TeacherSchedule::query()
                ->with(['teacher:id,name', 'lesson:id,name'])
                ->where('class_id', $selectedClassId)
                ->where('is_active', true)
                ->whereIn('day_of_week', $selectedDays->all())
                ->whereNotNull('period_no')
                ->where('period_no', '<=', $periodCount)
                ->orderBy('day_of_week')
                ->orderBy('period_no')
                ->get();
        }

        $timeline = $this->buildTimeline($periodCount, $settings);
        $periodTimeMap = collect($timeline)
            ->where('type', 'period')
            ->keyBy('period_no')
            ->toArray();

        $teacherProgramMap = [];
        foreach ($teacherProgram as $slot) {
            $teacherProgramMap[$slot->day_of_week][$slot->period_no] = $slot;
        }

        $classProgramMap = [];
        foreach ($classProgram as $slot) {
            $classProgramMap[$slot->day_of_week][$slot->period_no] = $slot;
        }

        return view('timetables.index', [
            'settings' => $settings,
            'dayOptions' => $dayOptions,
            'selectedDays' => $selectedDays,
            'periodCount' => $periodCount,
            'periods' => $periods,
            'classes' => $classes,
            'classesForGrid' => $classesForGrid,
            'teachers' => $teachers,
            'lessons' => $lessons,
            'slotMap' => $slotMap,
            'teacherProgram' => $teacherProgram,
            'classProgram' => $classProgram,
            'teacherProgramMap' => $teacherProgramMap,
            'classProgramMap' => $classProgramMap,
            'timeline' => $timeline,
            'periodTimeMap' => $periodTimeMap,
            'selectedTeacherId' => $selectedTeacherId,
            'selectedClassId' => $selectedClassId,
            'selectedGridClassId' => $selectedGridClassId,
            'currentUser' => $currentUser,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'day_start_time' => 'required|date_format:H:i',
            'lesson_duration' => 'required|integer|min:20|max:90',
            'short_break_duration' => 'required|integer|min:0|max:30',
            'lunch_after_period' => 'nullable|integer|min:1|max:12',
            'lunch_duration' => 'required|integer|min:10|max:120',
            'period_count' => 'nullable|integer|min:1|max:12',
            'grid_class_id' => 'nullable|integer|min:0',
            'days' => 'nullable|array',
            'days.*' => 'integer|min:1|max:7',
        ]);

        $settings = $this->getOrCreateSettings();
        $settings->update([
            'day_start_time' => $data['day_start_time'] . ':00',
            'lesson_duration' => (int) $data['lesson_duration'],
            'short_break_duration' => (int) $data['short_break_duration'],
            'lunch_after_period' => isset($data['lunch_after_period']) ? (int) $data['lunch_after_period'] : null,
            'lunch_duration' => (int) $data['lunch_duration'],
        ]);

        return redirect()->route('timetables.index', [
            'period_count' => (int) ($data['period_count'] ?? 9),
            'grid_class_id' => (int) ($data['grid_class_id'] ?? 0),
            'days' => $data['days'] ?? [1, 2, 3, 4, 5],
        ])->with('status', 'Ders saati ayarlari kaydedildi.');
    }

    public function store(Request $request)
    {
        $hasLessonShortName = \Illuminate\Support\Facades\Schema::hasColumn('lessons', 'short_name');

        $data = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'teacher_id' => 'required|exists:users,id',
            'lesson_id' => 'required|exists:lessons,id',
            'day_of_week' => 'required|integer|min:1|max:7',
            'period_no' => 'required|integer|min:1|max:12',
            'period_count' => 'nullable|integer|min:1|max:12',
            'grid_class_id' => 'nullable|integer|min:0',
            'days' => 'nullable|array',
            'days.*' => 'integer|min:1|max:7',
        ]);

        $classId = (int) $data['class_id'];
        $day = (int) $data['day_of_week'];
        $period = (int) $data['period_no'];
        $teacherId = (int) $data['teacher_id'];
        $lessonId = (int) $data['lesson_id'];

        if (! $this->teacherHasLesson($teacherId, $lessonId)) {
            return back()->withErrors(['teacher_id' => 'Ogretmen secilen derse eslestirilmemis.'])->withInput();
        }

        $existingSlot = TeacherSchedule::query()
            ->where('class_id', $classId)
            ->where('day_of_week', $day)
            ->where('period_no', $period)
            ->first();

        if ($this->hasTeacherConflict($teacherId, $day, $period, $existingSlot?->id)) {
            return back()->withErrors(['teacher_id' => 'Ayni ogretmen ayni gun/saatte farkli sinifa atanamaz.'])->withInput();
        }

        $lesson = Lesson::findOrFail($lessonId);
        [$start, $end] = $this->slotTimeRange($period);

        TeacherSchedule::updateOrCreate(
            [
                'class_id' => $classId,
                'day_of_week' => $day,
                'period_no' => $period,
            ],
            [
                'teacher_id' => $teacherId,
                'lesson_id' => $lesson->id,
                'lesson_name' => ($hasLessonShortName ? ($lesson->short_name ?: $lesson->name) : $lesson->name),
                'start_time' => $start,
                'end_time' => $end,
                'is_active' => true,
            ]
        );

        $savedSlot = TeacherSchedule::query()
            ->with(['teacher:id,name', 'lesson:id,name'])
            ->where('class_id', $classId)
            ->where('day_of_week', $day)
            ->where('period_no', $period)
            ->first();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => 'Ders programi slotu kaydedildi.',
                'slot' => [
                    'id' => $savedSlot?->id,
                    'class_id' => $classId,
                    'day_of_week' => $day,
                    'period_no' => $period,
                    'lesson_name' => $savedSlot?->lesson?->name ?? $savedSlot?->lesson_name,
                    'teacher_name' => $savedSlot?->teacher?->name,
                ],
            ]);
        }

        return redirect()->route('timetables.index', [
            'period_count' => (int) ($data['period_count'] ?? 9),
            'grid_class_id' => (int) ($data['grid_class_id'] ?? 0),
            'days' => $data['days'] ?? [1, 2, 3, 4, 5],
        ])->with('status', 'Ders programi slotu kaydedildi.');
    }

    public function move(Request $request)
    {
        $data = $request->validate([
            'source_schedule_id' => 'required|exists:teacher_schedules,id',
            'target_class_id' => 'required|exists:classes,id',
            'target_day_of_week' => 'required|integer|min:1|max:7',
            'target_period_no' => 'required|integer|min:1|max:12',
        ]);

        $schedule = TeacherSchedule::with('teacher', 'lesson')->findOrFail((int) $data['source_schedule_id']);
        $currentUser = $request->user();
        if (! $currentUser->hasRole('admin') && $schedule->teacher_id !== $currentUser->id) {
            abort(403);
        }

        $targetClassId = (int) $data['target_class_id'];
        $targetDay = (int) $data['target_day_of_week'];
        $targetPeriod = (int) $data['target_period_no'];

        $occupied = TeacherSchedule::query()
            ->where('class_id', $targetClassId)
            ->where('day_of_week', $targetDay)
            ->where('period_no', $targetPeriod)
            ->where('id', '!=', $schedule->id)
            ->exists();

        if ($occupied) {
            return response()->json(['ok' => false, 'message' => 'Hedef hucre dolu.'], 422);
        }

        if ($this->hasTeacherConflict($schedule->teacher_id, $targetDay, $targetPeriod, $schedule->id)) {
            return response()->json(['ok' => false, 'message' => 'Ayni ogretmen ayni gun/saatte farkli sinifa atanamaz.'], 422);
        }

        [$start, $end] = $this->slotTimeRange($targetPeriod);

        $schedule->update([
            'class_id' => $targetClassId,
            'day_of_week' => $targetDay,
            'period_no' => $targetPeriod,
            'start_time' => $start,
            'end_time' => $end,
            'is_active' => true,
        ]);

        return response()->json(['ok' => true, 'message' => 'Slot tasindi.']);
    }

    public function import(Request $request)
    {
        $hasLessonShortName = \Illuminate\Support\Facades\Schema::hasColumn('lessons', 'short_name');
        $gridClassId = (int) $request->input('grid_class_id', 0);
        $rows = $this->extractRowsFromRequest($request);
        if (empty($rows)) {
            return $this->failure($request, 'Dosya okunamadi veya satir bulunamadi.');
        }

        $classesByName = SchoolClass::query()->get(['id', 'name'])->keyBy(fn ($c) => mb_strtolower(trim($c->name)));
        $teachersByEmail = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'teacher'))
            ->get(['id', 'name', 'email'])
            ->keyBy(fn ($t) => mb_strtolower(trim((string) $t->email)));
        $teachersByName = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'teacher'))
            ->get(['id', 'name'])
            ->keyBy(fn ($t) => mb_strtolower(trim((string) $t->name)));
        $lessonsRaw = Lesson::query()->where('is_active', true)->get($lessonSelect);
        $lessonsByName = $lessonsRaw->keyBy(fn ($l) => mb_strtolower(trim((string) $l->name)));
        foreach ($lessonsRaw as $lessonRow) {
            if ($hasLessonShortName) {
                $short = mb_strtolower(trim((string) ($lessonRow->short_name ?? '')));
                if ($short !== '' && ! $lessonsByName->has($short)) {
                    $lessonsByName->put($short, $lessonRow);
                }
            }
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $i => $row) {
            $lineNo = $i + 2;

            $classId = $this->resolveClassId($row, $classesByName);
            $day = $this->resolveDay($row['gun'] ?? $row['day_of_week'] ?? null);
            $period = (int) ($row['ders_saati'] ?? $row['period_no'] ?? 0);
            $teacherId = $this->resolveTeacherId($row, $teachersByEmail, $teachersByName);
            $lessonId = $this->resolveLessonId($row, $lessonsByName);

            if (! $classId || ! $day || $period < 1 || $period > 12 || ! $teacherId || ! $lessonId) {
                $skipped++;
                $errors[] = "Satir {$lineNo}: Gerekli alanlar gecersiz.";
                continue;
            }

            if (! $this->teacherHasLesson($teacherId, $lessonId)) {
                $skipped++;
                $errors[] = "Satir {$lineNo}: Ogretmen secilen derse eslestirilmemis.";
                continue;
            }

            $existing = TeacherSchedule::query()
                ->where('class_id', $classId)
                ->where('day_of_week', $day)
                ->where('period_no', $period)
                ->first();

            if ($this->hasTeacherConflict($teacherId, $day, $period, $existing?->id)) {
                $skipped++;
                $errors[] = "Satir {$lineNo}: Ogretmen saat cakismasi.";
                continue;
            }

            $lesson = Lesson::find($lessonId);
            if (! $lesson) {
                $skipped++;
                $errors[] = "Satir {$lineNo}: Ders bulunamadi.";
                continue;
            }

            [$start, $end] = $this->slotTimeRange($period);

            $slot = TeacherSchedule::updateOrCreate(
                [
                    'class_id' => $classId,
                    'day_of_week' => $day,
                    'period_no' => $period,
                ],
                [
                    'teacher_id' => $teacherId,
                    'lesson_id' => $lesson->id,
                    'lesson_name' => ($hasLessonShortName ? ($lesson->short_name ?: $lesson->name) : $lesson->name),
                    'start_time' => $start,
                    'end_time' => $end,
                    'is_active' => true,
                ]
            );

            if ($slot->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        $message = "Toplu yukleme tamamlandi. Yeni: {$created}, Guncellenen: {$updated}, Atlanan: {$skipped}";
        if (! empty($errors)) {
            $message .= ' | ' . implode(' ', array_slice($errors, 0, 5));
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }

        return redirect()->route('timetables.index', [
            'period_count' => (int) $request->input('period_count', 9),
            'grid_class_id' => $gridClassId,
            'days' => $request->input('days', [1, 2, 3, 4, 5]),
        ])->with('status', $message);
    }

    public function downloadTemplate(): StreamedResponse
    {
        $rows = [
            ['sinif', 'gun', 'ders_saati', 'ogretmen_e_posta', 'ders'],
            ['5-A', 'Pazartesi', '1', 'ayse@example.com', 'Matematik'],
            ['5-A', 'Pazartesi', '2', 'ayse@example.com', 'Turkce'],
        ];

        return response()->streamDownload(function () use ($rows) {
            foreach ($rows as $row) {
                echo implode("\t", $row) . "\r\n";
            }
        }, 'ders_programi_sablonu.xls', [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    public function destroy(Request $request, TeacherSchedule $schedule)
    {
        $schedule->delete();

        return redirect()->route('timetables.index', [
            'period_count' => (int) $request->input('period_count', 9),
            'grid_class_id' => (int) $request->input('grid_class_id', 0),
            'days' => $request->input('days', [1, 2, 3, 4, 5]),
        ])->with('status', 'Ders programi slotu silindi.');
    }

    private function hasTeacherConflict(int $teacherId, int $day, int $period, ?int $ignoreId = null): bool
    {
        return TeacherSchedule::query()
            ->where('teacher_id', $teacherId)
            ->where('day_of_week', $day)
            ->where('period_no', $period)
            ->where('is_active', true)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }

    private function teacherHasLesson(int $teacherId, int $lessonId): bool
    {
        return User::query()
            ->where('id', $teacherId)
            ->whereHas('lessons', fn ($q) => $q->where('lessons.id', $lessonId))
            ->exists();
    }

    private function slotTimeRange(int $periodNo): array
    {
        $settings = $this->getOrCreateSettings();
        $period = collect($this->buildTimeline($periodNo, $settings))
            ->first(fn ($item) => $item['type'] === 'period' && $item['period_no'] === $periodNo);

        if (! $period) {
            $base = Carbon::createFromTimeString((string) $settings->day_start_time);
            $start = $base->copy()->addMinutes(($periodNo - 1) * ((int) $settings->lesson_duration + (int) $settings->short_break_duration));
            $end = $start->copy()->addMinutes((int) $settings->lesson_duration);
            return [$start->format('H:i:s'), $end->format('H:i:s')];
        }

        return [$period['start'], $period['end']];
    }

    private function getOrCreateSettings(): TimetableSetting
    {
        return TimetableSetting::query()->firstOrCreate(
            ['id' => 1],
            [
                'day_start_time' => '08:50:00',
                'lesson_duration' => 35,
                'short_break_duration' => 10,
                'lunch_after_period' => 6,
                'lunch_duration' => 40,
            ]
        );
    }

    private function buildTimeline(int $periodCount, TimetableSetting $settings): array
    {
        $columns = [];
        $cursor = Carbon::createFromTimeString((string) $settings->day_start_time);
        $lessonDuration = (int) $settings->lesson_duration;
        $shortBreak = (int) $settings->short_break_duration;
        $lunchAfter = $settings->lunch_after_period ? (int) $settings->lunch_after_period : null;
        $lunchDuration = (int) $settings->lunch_duration;

        for ($period = 1; $period <= $periodCount; $period++) {
            $start = $cursor->copy();
            $end = $start->copy()->addMinutes($lessonDuration);
            $columns[] = [
                'type' => 'period',
                'period_no' => $period,
                'title' => (string) $period,
                'start' => $start->format('H:i:s'),
                'end' => $end->format('H:i:s'),
            ];
            $cursor = $end->copy();

            if ($lunchAfter !== null && $period === $lunchAfter) {
                $lunchStart = $cursor->copy();
                $lunchEnd = $lunchStart->copy()->addMinutes($lunchDuration);
                $columns[] = [
                    'type' => 'lunch',
                    'title' => 'OGLE ARASI',
                    'start' => $lunchStart->format('H:i:s'),
                    'end' => $lunchEnd->format('H:i:s'),
                ];
                $cursor = $lunchEnd->copy();
            } elseif ($period < $periodCount && $shortBreak > 0) {
                $cursor = $cursor->copy()->addMinutes($shortBreak);
            }
        }

        return $columns;
    }

    private function extractRowsFromRequest(Request $request): array
    {
        $jsonRows = $request->input('parsed_rows_json');
        if (is_string($jsonRows) && $jsonRows !== '') {
            $decoded = json_decode($jsonRows, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map(
                    fn ($row) => is_array($row) ? $this->normalizeRowKeys($row) : null,
                    $decoded
                )));
            }
        }

        $file = $this->validateImportFile($request);
        return $this->parseImportFile($file);
    }

    private function validateImportFile(Request $request): UploadedFile
    {
        $request->validate(['import_file' => 'required|file|max:5120']);
        /** @var UploadedFile $file */
        $file = $request->file('import_file');
        $ext = strtolower((string) $file->getClientOriginalExtension());
        if (! in_array($ext, ['csv', 'txt', 'xls', 'xlsx'], true)) {
            abort(422, 'Desteklenmeyen dosya tipi.');
        }
        return $file;
    }

    private function parseImportFile(UploadedFile $file): array
    {
        $content = file_get_contents($file->getRealPath()) ?: '';
        return $this->parseDelimitedContent($content, true);
    }

    private function parseDelimitedContent(string $content, bool $tryHtmlTable = false): array
    {
        $content = trim($content);
        if ($content === '') {
            return [];
        }

        if ($tryHtmlTable && str_contains(strtolower($content), '<table')) {
            return $this->parseHtmlTableContent($content);
        }

        $lines = preg_split('/\r\n|\n|\r/', $content) ?: [];
        if (empty($lines)) {
            return [];
        }

        $delimiter = $this->detectDelimiter($lines[0]);
        $headers = array_map(fn ($h) => $this->normalizeHeader((string) $h), str_getcsv($lines[0], $delimiter));

        $rows = [];
        foreach (array_slice($lines, 1) as $line) {
            if (trim($line) === '') {
                continue;
            }
            $values = str_getcsv($line, $delimiter);
            $row = [];
            foreach ($headers as $i => $header) {
                $row[$header] = isset($values[$i]) ? trim((string) $values[$i]) : null;
            }
            $rows[] = $row;
        }

        return $rows;
    }

    private function parseHtmlTableContent(string $content): array
    {
        $rows = [];
        $headers = [];
        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $content, $trMatches);
        foreach ($trMatches[1] as $trIndex => $trInner) {
            preg_match_all('/<t[hd][^>]*>(.*?)<\/t[hd]>/is', $trInner, $cellMatches);
            $cells = array_map(static fn ($cell) => trim(strip_tags(html_entity_decode($cell))), $cellMatches[1]);
            if ($trIndex === 0) {
                $headers = array_map(fn ($h) => $this->normalizeHeader((string) $h), $cells);
                continue;
            }
            if (empty($cells)) {
                continue;
            }
            $row = [];
            foreach ($headers as $i => $header) {
                $row[$header] = $cells[$i] ?? null;
            }
            $rows[] = $row;
        }
        return $rows;
    }

    private function detectDelimiter(string $line): string
    {
        $delimiters = [',', ';', "\t", '|'];
        $scores = [];
        foreach ($delimiters as $d) {
            $scores[$d] = count(str_getcsv($line, $d));
        }
        arsort($scores);
        return (string) array_key_first($scores);
    }

    private function normalizeHeader(string $header): string
    {
        $h = preg_replace('/^\xEF\xBB\xBF/u', '', $header) ?? $header;
        $h = mb_strtolower(trim($h));
        $h = str_replace([' ', '-', '/'], '_', $h);
        $h = preg_replace('/_+/', '_', $h) ?? $h;
        $h = trim($h, "_ \t\n\r\0\x0B");

        return [
            'sinif' => 'class_name',
            'sinif_adi' => 'class_name',
            'class' => 'class_name',
            'class_name' => 'class_name',
            'sinif_id' => 'class_id',
            'class_id' => 'class_id',
            'gun' => 'gun',
            'day' => 'gun',
            'day_of_week' => 'day_of_week',
            'ders_saati' => 'period_no',
            'period' => 'period_no',
            'period_no' => 'period_no',
            'ogretmen_e_posta' => 'teacher_email',
            'teacher_email' => 'teacher_email',
            'ogretmen' => 'teacher_name',
            'teacher' => 'teacher_name',
            'ders' => 'lesson_name',
            'lesson' => 'lesson_name',
            'lesson_name' => 'lesson_name',
            'ders_id' => 'lesson_id',
            'lesson_id' => 'lesson_id',
            'ogretmen_id' => 'teacher_id',
            'teacher_id' => 'teacher_id',
        ][$h] ?? $h;
    }

    private function normalizeRowKeys(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[$this->normalizeHeader((string) $key)] = is_string($value) ? trim($value) : $value;
        }
        return $normalized;
    }

    private function resolveClassId(array $row, $classesByName): ?int
    {
        if (! empty($row['class_id'])) {
            return (int) $row['class_id'];
        }

        $name = mb_strtolower(trim((string) ($row['class_name'] ?? '')));
        if ($name === '') {
            return null;
        }

        return $classesByName[$name]->id ?? null;
    }

    private function resolveTeacherId(array $row, $teachersByEmail, $teachersByName): ?int
    {
        if (! empty($row['teacher_id'])) {
            return (int) $row['teacher_id'];
        }

        $email = mb_strtolower(trim((string) ($row['teacher_email'] ?? '')));
        if ($email !== '' && isset($teachersByEmail[$email])) {
            return (int) $teachersByEmail[$email]->id;
        }

        $name = mb_strtolower(trim((string) ($row['teacher_name'] ?? '')));
        if ($name !== '' && isset($teachersByName[$name])) {
            return (int) $teachersByName[$name]->id;
        }

        return null;
    }

    private function resolveLessonId(array $row, $lessonsByName): ?int
    {
        if (! empty($row['lesson_id'])) {
            return (int) $row['lesson_id'];
        }

        $name = mb_strtolower(trim((string) ($row['lesson_name'] ?? '')));
        if ($name === '') {
            return null;
        }

        return $lessonsByName[$name]->id ?? null;
    }

    private function resolveDay(mixed $value): ?int
    {
        if (is_numeric($value)) {
            $v = (int) $value;
            return ($v >= 1 && $v <= 7) ? $v : null;
        }

        $txt = mb_strtolower(trim((string) $value));
        if ($txt === '') {
            return null;
        }

        $map = [
            'pazartesi' => 1,
            'sali' => 2,
            'salı' => 2,
            'carsamba' => 3,
            'çarşamba' => 3,
            'persembe' => 4,
            'perşembe' => 4,
            'cuma' => 5,
            'cumartesi' => 6,
            'pazar' => 7,
        ];

        return $map[$txt] ?? null;
    }

    private function success(Request $request, string $message)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }
        return back()->with('status', $message);
    }

    private function failure(Request $request, string $message)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => false, 'message' => $message], 422);
        }
        return back()->withErrors(['import_file' => $message]);
    }
}

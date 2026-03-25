<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Lesson;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\TeacherSchedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $dayOfWeek = (int) Carbon::parse($date)->dayOfWeekIso;
        $currentUser = auth()->user();

        $scheduleQuery = TeacherSchedule::query()
            ->with('teacher:id,name', 'class:id,name,grade_level,section', 'lesson:id,name')
            ->where('is_active', true)
            ->where('day_of_week', $dayOfWeek)
            ->orderBy('start_time');

        if (! $currentUser->hasRole('admin')) {
            $scheduleQuery->where('teacher_id', $currentUser->id);
        } elseif ($request->filled('teacher_id')) {
            $scheduleQuery->where('teacher_id', $request->integer('teacher_id'));
        }

        $schedules = $scheduleQuery->get();
        $selectedSchedule = $schedules->firstWhere('id', (int) $request->input('schedule_id')) ?? $schedules->first();

        $students = collect();
        $statusByStudentId = [];
        $session = null;

        if ($selectedSchedule) {
            $students = Student::query()
                ->with('user:id,name,email')
                ->where('class_id', $selectedSchedule->class_id)
                ->orderBy('student_number')
                ->get();

            $session = AttendanceSession::query()
                ->where('schedule_id', $selectedSchedule->id)
                ->whereDate('attendance_date', $date)
                ->first();

            if ($session) {
                $statusByStudentId = AttendanceRecord::query()
                    ->where('session_id', $session->id)
                    ->pluck('status', 'student_id')
                    ->toArray();
            }
        }

        $teachers = User::query()
            ->whereHas('roles', fn($q) => $q->where('name', 'teacher'))
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
        $classes = SchoolClass::query()->select('id', 'name')->orderBy('name')->get();
        $lessons = Lesson::query()->where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        return view('attendance.index', compact(
            'date',
            'dayOfWeek',
            'schedules',
            'selectedSchedule',
            'students',
            'statusByStudentId',
            'session',
            'teachers',
            'classes',
            'lessons'
        ));
    }

    public function storeSchedule(Request $request)
    {
        $currentUser = auth()->user();
        if (! $currentUser->hasRole('admin') && ! $currentUser->hasRole('teacher')) {
            abort(403);
        }

        $data = $request->validate([
            'teacher_id' => 'nullable|exists:users,id',
            'class_id' => 'required|exists:classes,id',
            'lesson_id' => 'required|exists:lessons,id',
            'day_of_week' => 'required|integer|min:1|max:7',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
        ]);

        $teacherId = $currentUser->hasRole('admin')
            ? (int) ($data['teacher_id'] ?? 0)
            : $currentUser->id;

        if (! $teacherId) {
            return back()->withErrors(['teacher_id' => 'Ogretmen seciniz.']);
        }

        $lesson = Lesson::findOrFail((int) $data['lesson_id']);
        $teacherHasLesson = User::where('id', $teacherId)
            ->whereHas('lessons', fn($q) => $q->where('lessons.id', $lesson->id))
            ->exists();
        if (! $teacherHasLesson) {
            return back()->withErrors(['lesson_id' => 'Secilen ogretmen bu derse eslestirilmemis.']);
        }

        TeacherSchedule::create([
            'teacher_id' => $teacherId,
            'class_id' => (int) $data['class_id'],
            'lesson_id' => $lesson->id,
            'lesson_name' => $lesson->name,
            'day_of_week' => (int) $data['day_of_week'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('status', 'Ders programi kaydedildi.');
    }

    public function take(Request $request)
    {
        $data = $request->validate([
            'schedule_id' => 'required|exists:teacher_schedules,id',
            'attendance_date' => 'required|date',
            'statuses' => 'array',
            'statuses.*' => 'in:present,absent,excused,medical',
        ]);

        $schedule = TeacherSchedule::with('class')->findOrFail((int) $data['schedule_id']);
        $currentUser = auth()->user();

        if (! $currentUser->hasRole('admin') && $schedule->teacher_id !== $currentUser->id) {
            abort(403);
        }

        $session = AttendanceSession::updateOrCreate(
            ['schedule_id' => $schedule->id, 'attendance_date' => $data['attendance_date']],
            [
                'teacher_id' => $schedule->teacher_id,
                'class_id' => $schedule->class_id,
                'lesson_name' => $schedule->lesson_name,
                'taken_at' => now(),
            ]
        );

        $students = Student::where('class_id', $schedule->class_id)->get();
        $statuses = $data['statuses'] ?? [];

        foreach ($students as $student) {
            AttendanceRecord::updateOrCreate(
                ['session_id' => $session->id, 'student_id' => $student->id],
                ['status' => $statuses[$student->id] ?? 'present']
            );
        }

        return back()->with('status', 'Yoklama kaydedildi.');
    }
}

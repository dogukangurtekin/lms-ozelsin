<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AttendanceSession;
use App\Models\Book;
use App\Models\Meeting;
use App\Models\TeacherSchedule;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $currentUser = auth()->user();
        $role = $currentUser?->roles()->value('name') ?? 'student';
        $today = Carbon::today();

        $stats = [
            'users' => User::count(),
            'books' => Book::count(),
            'assignments' => Assignment::count(),
            'meetings' => Meeting::count(),
            'submissions' => AssignmentSubmission::count(),
            'graded_submissions' => AssignmentSubmission::whereNotNull('score')->count(),
            'today_meetings' => Meeting::whereDate('meeting_at', $today)->count(),
        ];

        $recentAssignments = Assignment::with(['teacher:id,name'])
            ->latest()
            ->limit(5)
            ->get(['id', 'teacher_id', 'title', 'due_at', 'created_at']);

        $upcomingMeetings = Meeting::with(['student:id,name', 'parentUser:id,name'])
            ->where('meeting_at', '>=', now())
            ->orderBy('meeting_at')
            ->limit(5)
            ->get(['id', 'student_id', 'parent_id', 'meeting_at', 'status']);

        $todayMeetings = collect();
        if ($currentUser && $currentUser->hasRole(['admin', 'teacher'])) {
            $todayMeetingQuery = Meeting::with(['student:id,name', 'parentUser:id,name', 'teacher:id,name'])
                ->whereDate('meeting_at', $today)
                ->orderBy('meeting_at');

            if ($currentUser->hasRole('teacher')) {
                $todayMeetingQuery->where('teacher_id', $currentUser->id);
            }

            $todayMeetings = $todayMeetingQuery
                ->limit(8)
                ->get(['id', 'teacher_id', 'student_id', 'parent_id', 'meeting_at', 'status']);
        }

        $recentAttendance = AttendanceSession::with(['class:id,name', 'schedule.teacher:id,name'])
            ->latest('attendance_date')
            ->limit(5)
            ->get(['id', 'schedule_id', 'class_id', 'lesson_name', 'attendance_date', 'taken_at']);

        $monthStart = now()->startOfMonth();
        $monthlyActivity = [
            'labels' => ['Ödev', 'Teslim', 'Görüşme', 'Yoklama'],
            'data' => [
                Assignment::where('created_at', '>=', $monthStart)->count(),
                AssignmentSubmission::where('created_at', '>=', $monthStart)->count(),
                Meeting::where('meeting_at', '>=', $monthStart)->count(),
                AttendanceSession::where('attendance_date', '>=', $monthStart->toDateString())->count(),
            ],
        ];

        $teacherDays = collect([
            1 => 'Pazartesi',
            2 => 'Salı',
            3 => 'Çarşamba',
            4 => 'Perşembe',
            5 => 'Cuma',
            6 => 'Cumartesi',
            7 => 'Pazar',
        ]);
        $teacherPeriods = collect();
        $teacherScheduleMap = [];
        $teacherPeriodTimeMap = [];

        if ($currentUser && $currentUser->hasRole('teacher')) {
            $teacherSchedules = TeacherSchedule::query()
                ->with(['class:id,name', 'lesson:id,name,short_name'])
                ->where('teacher_id', $currentUser->id)
                ->where('is_active', true)
                ->orderBy('day_of_week')
                ->orderBy('period_no')
                ->get();

            $teacherPeriods = $teacherSchedules
                ->pluck('period_no')
                ->filter()
                ->unique()
                ->sort()
                ->values();

            foreach ($teacherSchedules as $slot) {
                $teacherScheduleMap[$slot->period_no][$slot->day_of_week] = $slot;
                if (! isset($teacherPeriodTimeMap[$slot->period_no])) {
                    $teacherPeriodTimeMap[$slot->period_no] = [
                        'start' => substr((string) $slot->start_time, 0, 5),
                        'end' => substr((string) $slot->end_time, 0, 5),
                    ];
                }
            }
        }

        return view('dashboard.index', compact(
            'role',
            'stats',
            'recentAssignments',
            'upcomingMeetings',
            'todayMeetings',
            'recentAttendance',
            'monthlyActivity',
            'teacherDays',
            'teacherPeriods',
            'teacherScheduleMap',
            'teacherPeriodTimeMap'
        ));
    }
}

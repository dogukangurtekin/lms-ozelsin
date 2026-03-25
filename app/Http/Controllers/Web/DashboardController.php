<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AttendanceSession;
use App\Models\Book;
use App\Models\Meeting;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $role = auth()->user()?->roles()->value('name') ?? 'student';
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

        $recentAttendance = AttendanceSession::with(['class:id,name', 'schedule.teacher:id,name'])
            ->latest('attendance_date')
            ->limit(5)
            ->get(['id', 'schedule_id', 'class_id', 'lesson_name', 'attendance_date', 'taken_at']);

        $activityLabels = [];
        $activityData = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $activityLabels[] = $day->format('d M');
            $activityData[] = AssignmentSubmission::whereDate('created_at', $day->toDateString())->count();
        }

        return view('dashboard.index', compact(
            'role',
            'stats',
            'recentAssignments',
            'upcomingMeetings',
            'recentAttendance',
            'activityLabels',
            'activityData'
        ));
    }
}

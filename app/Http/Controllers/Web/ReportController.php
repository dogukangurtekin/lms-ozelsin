<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $students = User::whereHas('roles', fn($q) => $q->where('name', 'student'))->count();
        $teachers = User::whereHas('roles', fn($q) => $q->where('name', 'teacher'))->count();
        $assignments = Assignment::count();
        $submissions = AssignmentSubmission::count();
        $completionRate = $assignments > 0 ? round(($submissions / $assignments) * 100, 2) : 0;

        $performance = AssignmentSubmission::selectRaw('student_id, AVG(score) as avg_score')
            ->whereNotNull('score')
            ->groupBy('student_id')
            ->with('student:id,name')
            ->get();

        $studentRecords = Student::query()
            ->select(
                'students.id',
                'students.student_number',
                'students.birth_date',
                'users.id as user_id',
                'users.name',
                'users.email',
                'users.is_active',
                'classes.name as class_name',
                DB::raw('COUNT(DISTINCT assignment_submissions.id) as total_submissions'),
                DB::raw('ROUND(AVG(assignment_submissions.score), 2) as avg_score')
            )
            ->join('users', 'users.id', '=', 'students.user_id')
            ->leftJoin('classes', 'classes.id', '=', 'students.class_id')
            ->leftJoin('assignment_submissions', 'assignment_submissions.student_id', '=', 'users.id')
            ->groupBy('students.id', 'students.student_number', 'students.birth_date', 'users.id', 'users.name', 'users.email', 'users.is_active', 'classes.name')
            ->orderByDesc('students.id')
            ->paginate(10);

        return view('reports.index', compact(
            'students',
            'teachers',
            'assignments',
            'submissions',
            'completionRate',
            'performance',
            'studentRecords'
        ));
    }
}

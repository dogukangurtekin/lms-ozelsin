<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AttendanceSession;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\TeacherSchedule;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $selectedClassIds = collect(request()->input('class_ids', []))
            ->map(fn($id) => (int) $id)
            ->filter()
            ->values();
        $reportType = request()->input('report_type', 'student_list');
        $sortBy = request()->input('sort_by', 'name');
        $selectedFields = collect(request()->input('fields', ['student_number', 'name', 'email']))
            ->map(fn($v) => (string) $v)
            ->values();

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

        $studentRecordsQuery = Student::query()
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
            ->groupBy('students.id', 'students.student_number', 'students.birth_date', 'users.id', 'users.name', 'users.email', 'users.is_active', 'classes.name');

        if ($selectedClassIds->isNotEmpty()) {
            $studentRecordsQuery->whereIn('students.class_id', $selectedClassIds->all());
        }

        if ($sortBy === 'number') {
            $studentRecordsQuery->orderBy('students.student_number');
        } elseif ($sortBy === 'score') {
            $studentRecordsQuery->orderByDesc(DB::raw('AVG(assignment_submissions.score)'));
        } else {
            $studentRecordsQuery->orderBy('users.name');
        }

        $studentRecords = $studentRecordsQuery->paginate(10)->withQueryString();

        $classes = SchoolClass::query()
            ->select('id', 'name', 'grade_level')
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();

        $maxPeriod = (int) (TeacherSchedule::query()->max('period_no') ?? 0);
        $lessonCountOptions = collect(range(1, max(5, $maxPeriod)))->values();

        $reportFieldOptions = [
            'student_number' => 'Numara',
            'name' => 'Ad Soyad',
            'email' => 'E-posta',
            'class_name' => 'Sınıf',
            'is_active' => 'Durum',
            'total_submissions' => 'Teslim Sayısı',
            'avg_score' => 'Ortalama Puan',
            'birth_date' => 'Doğum Tarihi',
        ];

        $quickReportStats = [
            'students_pdf' => $students,
            'students_excel' => $students,
            'attendance_pdf' => AttendanceSession::query()
                ->whereNotNull('taken_at')
                ->whereDate('attendance_date', now()->toDateString())
                ->count(),
        ];

        return view('reports.index', compact(
            'students',
            'teachers',
            'assignments',
            'submissions',
            'completionRate',
            'performance',
            'studentRecords',
            'classes',
            'lessonCountOptions',
            'quickReportStats'
            ,'selectedClassIds'
            ,'reportType'
            ,'sortBy'
            ,'selectedFields'
            ,'reportFieldOptions'
        ));
    }

    public function quickStudentPdf(\Illuminate\Http\Request $request)
    {
        $classId = (int) $request->input('class_id', 0);

        $query = Student::query()
            ->with(['user:id,name,email', 'class:id,name'])
            ->join('users', 'users.id', '=', 'students.user_id')
            ->orderBy('users.name')
            ->select('students.*');

        if ($classId > 0) {
            $query->where('students.class_id', $classId);
        }

        $students = $query->get();
        $selectedClass = $classId > 0 ? SchoolClass::query()->find($classId) : null;

        $html = view('reports.pdf.student-list', [
            'students' => $students,
            'selectedClass' => $selectedClass,
            'generatedAt' => now(),
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="ogrenci-listesi.pdf"',
        ]);
    }

    public function quickAttendancePdf(\Illuminate\Http\Request $request)
    {
        $lessonCount = max(1, (int) $request->input('lesson_count', 5));
        $date = now()->toDateString();

        $sessions = AttendanceSession::query()
            ->with('class:id,name')
            ->whereDate('attendance_date', $date)
            ->orderBy('class_id')
            ->orderBy('lesson_name')
            ->limit($lessonCount)
            ->get();

        $html = view('reports.pdf.attendance-list', [
            'sessions' => $sessions,
            'lessonCount' => $lessonCount,
            'date' => $date,
            'generatedAt' => now(),
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="yoklama-listesi.pdf"',
        ]);
    }
}

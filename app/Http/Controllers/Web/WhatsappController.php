<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Meeting;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\WhatsappService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WhatsappController extends Controller
{
    public function index(Request $request)
    {
        $classes = SchoolClass::query()
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get(['id', 'name', 'grade_level']);

        $studentsQuery = Student::query()
            ->with(['user:id,name,phone,is_active', 'class:id,name,grade_level,section'])
            ->join('users', 'users.id', '=', 'students.user_id')
            ->orderBy('users.name')
            ->select('students.*');

        if ($request->filled('class_id')) {
            $studentsQuery->where('students.class_id', (int) $request->input('class_id'));
        }

        $students = $studentsQuery->get();

        return view('whatsapp.index', [
            'classes' => $classes,
            'students' => $students,
            'selectedClassId' => (int) $request->input('class_id', 0),
        ]);
    }

    public function send(Request $request, WhatsappService $whatsappService)
    {
        $data = $request->validate([
            'class_id' => 'nullable|exists:classes,id',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'report_fields' => 'required|array|min:1',
            'report_fields.*' => 'in:identity,attendance,assignments,performance,meetings,status',
            'message_note' => 'nullable|string|max:1000',
        ]);

        $selectedFields = array_values(array_unique($data['report_fields']));

        $studentsQuery = Student::query()
            ->with(['user:id,name,phone,email,is_active', 'class:id,name,grade_level,section'])
            ->whereIn('students.id', $data['student_ids']);

        if (! empty($data['class_id'])) {
            $studentsQuery->where('students.class_id', (int) $data['class_id']);
        }

        $students = $studentsQuery->get();

        if ($students->isEmpty()) {
            return back()->withErrors(['student_ids' => 'Secili filtreye uygun ogrenci bulunamadi.'])->withInput();
        }

        $queued = 0;
        $skipped = 0;

        foreach ($students as $student) {
            $parents = DB::table('parent_student')
                ->join('parents', 'parents.id', '=', 'parent_student.parent_id')
                ->join('users', 'users.id', '=', 'parents.user_id')
                ->where('parent_student.student_id', $student->id)
                ->whereNotNull('users.phone')
                ->select('users.id', 'users.name', 'users.phone')
                ->get();

            if ($parents->isEmpty()) {
                $skipped++;
                continue;
            }

            $reportData = $this->buildStudentReportData($student, $selectedFields);
            $pdfPath = $this->generateStudentPdf($student, $reportData, $selectedFields);
            $pdfUrl = url(Storage::url($pdfPath));

            foreach ($parents as $parent) {
                $message = "Sayin {$parent->name}, {$student->user?->name} ogrencisine ait rapor hazirdir.\n";
                if (! empty($data['message_note'])) {
                    $message .= trim((string) $data['message_note']) . "\n";
                }
                $message .= "PDF Rapor: {$pdfUrl}";

                $whatsappService->queueMessage([
                    'sender_id' => $request->user()->id,
                    'receiver_id' => (int) $parent->id,
                    'receiver_phone' => (string) $parent->phone,
                    'type' => 'performance',
                    'content' => $message,
                ]);
                $queued++;
            }
        }

        if ($queued === 0) {
            return back()->withErrors(['student_ids' => 'Veli telefonu/eşleşmesi bulunamadığı için gönderim yapılamadı.'])->withInput();
        }

        return back()->with('status', "WhatsApp modulunde kuyruga alindi. Gonderim: {$queued}, Atlanan ogrenci: {$skipped}");
    }

    private function buildStudentReportData(Student $student, array $fields): array
    {
        $studentUserId = (int) $student->user_id;
        $data = [];

        if (in_array('identity', $fields, true)) {
            $data['identity'] = [
                'name' => $student->user?->name,
                'email' => $student->user?->email,
                'phone' => $student->user?->phone,
                'student_number' => $student->student_number,
                'class_name' => $student->class?->name,
                'section' => $student->class?->section,
            ];
        }

        if (in_array('attendance', $fields, true) && Schema::hasTable('attendance_records')) {
            $attendance = DB::table('attendance_records')
                ->where('student_id', $student->id)
                ->selectRaw("SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present_count")
                ->selectRaw("SUM(CASE WHEN status='absent' THEN 1 ELSE 0 END) as absent_count")
                ->selectRaw("SUM(CASE WHEN status='excused' THEN 1 ELSE 0 END) as excused_count")
                ->selectRaw("SUM(CASE WHEN status='medical' THEN 1 ELSE 0 END) as medical_count")
                ->first();
            $data['attendance'] = (array) ($attendance ?? []);
        }

        if (in_array('assignments', $fields, true)) {
            $classId = $student->class_id;
            $totalAssignments = Assignment::query()
                ->where(function ($q) use ($studentUserId, $classId) {
                    $q->where('student_id', $studentUserId)
                        ->orWhere(function ($cq) use ($classId) {
                            $cq->whereNull('student_id')->where('class_id', $classId);
                        })
                        ->orWhere(function ($aq) {
                            $aq->whereNull('student_id')->whereNull('class_id');
                        });
                })
                ->count();

            $submittedCount = AssignmentSubmission::query()->where('student_id', $studentUserId)->count();
            $data['assignments'] = [
                'total' => $totalAssignments,
                'submitted' => $submittedCount,
            ];
        }

        if (in_array('performance', $fields, true)) {
            $avgScore = AssignmentSubmission::query()->where('student_id', $studentUserId)->whereNotNull('score')->avg('score');
            $latest = AssignmentSubmission::query()
                ->where('student_id', $studentUserId)
                ->latest('id')
                ->limit(5)
                ->get(['score', 'submitted_at'])
                ->toArray();

            $data['performance'] = [
                'avg_score' => $avgScore ? round((float) $avgScore, 2) : null,
                'latest' => $latest,
            ];
        }

        if (in_array('meetings', $fields, true)) {
            $lastMeeting = Meeting::query()->where('student_id', $studentUserId)->latest('meeting_at')->first(['meeting_at', 'status', 'notes']);
            $data['meetings'] = [
                'last_meeting' => $lastMeeting,
            ];
        }

        if (in_array('status', $fields, true)) {
            $data['status'] = [
                'is_active' => (bool) ($student->user?->is_active ?? false),
                'birth_date' => $student->birth_date,
            ];
        }

        return $data;
    }

    private function generateStudentPdf(Student $student, array $reportData, array $selectedFields): string
    {
        $html = view('whatsapp.student-report-pdf', [
            'student' => $student,
            'reportData' => $reportData,
            'selectedFields' => $selectedFields,
            'generatedAt' => now(),
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $safeName = Str::slug((string) ($student->user?->name ?? 'ogrenci'));
        $path = 'whatsapp-reports/'.$student->id.'/rapor-'.$safeName.'-'.now()->format('YmdHis').'.pdf';
        Storage::disk('public')->put($path, $dompdf->output());

        return $path;
    }
}


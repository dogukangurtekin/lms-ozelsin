<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsappMessageJob;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Meeting;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\WhatsappLog;
use App\Models\WhatsappSetting;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class WhatsappController extends Controller
{
    public function index(Request $request)
    {
        $classes = SchoolClass::query()
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get(['id', 'name', 'grade_level', 'section']);

        $sections = $classes
            ->pluck('section')
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v) => (string) $v)
            ->unique()
            ->sort()
            ->values();

        $selectedClassId = (int) $request->input('class_id', 0);
        $selectedSection = trim((string) $request->input('section', ''));
        $selectedTargetType = (string) $request->input('target_type', 'parent_of_students');
        $settings = WhatsappSetting::query()->firstOrCreate(['id' => 1], ['sender_phone' => null]);

        $students = collect();
        if ($selectedTargetType === 'parent_of_students') {
            $studentsQuery = Student::query()
                ->with(['user:id,name,phone,is_active', 'class:id,name,grade_level,section'])
                ->join('users', 'users.id', '=', 'students.user_id')
                ->orderBy('users.name')
                ->select('students.*');

            if ($selectedClassId > 0) {
                $studentsQuery->where('students.class_id', $selectedClassId);
            }

            if ($selectedSection !== '') {
                $studentsQuery->whereHas('class', fn ($q) => $q->where('section', $selectedSection));
            }

            $students = $studentsQuery->get();
        }

        $manualLinks = collect(session('manual_links', []));

        return view('whatsapp.index', [
            'classes' => $classes,
            'sections' => $sections,
            'students' => $students,
            'selectedClassId' => $selectedClassId,
            'selectedSection' => $selectedSection,
            'selectedTargetType' => $selectedTargetType,
            'senderPhone' => (string) ($settings->sender_phone ?? ''),
            'manualLinks' => $manualLinks,
        ]);
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'target_type' => 'required|in:parent_of_students,teachers,parents,all_active',
            'class_id' => 'nullable|exists:classes,id',
            'section' => 'nullable|string|max:20',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
            'report_fields' => 'nullable|array',
            'report_fields.*' => 'in:identity,attendance,assignments,performance,meetings,status',
            'message_note' => 'nullable|string|max:1000',
            'message_template' => 'nullable|string|max:2000',
        ]);

        $targetType = (string) $data['target_type'];
        $classId = (int) ($data['class_id'] ?? 0);
        $section = trim((string) ($data['section'] ?? ''));

        if ($targetType === 'parent_of_students' && empty($data['report_fields'])) {
            return back()->withErrors(['report_fields' => 'En az bir rapor alani secmelisiniz.'])->withInput();
        }

        if ($targetType !== 'parent_of_students' && trim((string) ($data['message_note'] ?? '')) === '') {
            return back()->withErrors(['message_note' => 'Bu hedef tipi icin mesaj notu zorunludur.'])->withInput();
        }

        $links = [];
        $skipped = 0;

        if ($targetType === 'parent_of_students') {
            $selectedFields = array_values(array_unique($data['report_fields'] ?? []));
            $template = trim((string) ($data['message_template'] ?? ''));
            if ($template === '') {
                $template = "Ozelsin Koleji Ortaokulu Bilgilendirme Sistemi.\nDegerli velim {veli_adi}, {ogrenci_adi} ogrencimize ait rapor hazirdir.\n{not}\nPDF Rapor: {pdf_link}";
            }

            $studentsQuery = Student::query()
                ->with(['user:id,name,phone,email,is_active', 'class:id,name,grade_level,section']);

            if ($classId > 0) {
                $studentsQuery->where('students.class_id', $classId);
            }

            if ($section !== '') {
                $studentsQuery->whereHas('class', fn ($q) => $q->where('section', $section));
            }

            if (! empty($data['student_ids'])) {
                $studentsQuery->whereIn('students.id', $data['student_ids']);
            }

            if (! $studentsQuery->exists()) {
                return back()->withErrors(['student_ids' => 'Secili filtreye uygun ogrenci bulunamadi.'])->withInput();
            }

            $studentsQuery->orderBy('id')->chunkById(100, function ($studentsChunk) use (&$links, &$skipped, $selectedFields, $data, $template) {
                foreach ($studentsChunk as $student) {
                    $parents = DB::table('parent_student')
                        ->join('parents', 'parents.id', '=', 'parent_student.parent_id')
                        ->join('users', 'users.id', '=', 'parents.user_id')
                        ->where('parent_student.student_id', $student->id)
                        ->whereNotNull('users.phone')
                        ->where('users.phone', '<>', '')
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
                        $message = $this->buildParentReportMessage(
                            $template,
                            (string) $parent->name,
                            (string) ($student->user?->name ?? ''),
                            trim((string) ($data['message_note'] ?? '')),
                            $pdfUrl
                        );
                        $phone = $this->normalizePhoneForWhatsAppLink((string) $parent->phone);
                        if ($phone === null) {
                            $skipped++;
                            continue;
                        }

                        $links[] = [
                            'receiver_name' => (string) $parent->name,
                            'student_name' => (string) ($student->user?->name ?? ''),
                            'phone' => '+' . $phone,
                            'message' => $message,
                            'wa_link' => $this->buildWhatsappDeepLink($phone, $message),
                            'pdf_link' => $pdfUrl,
                        ];
                    }
                }
            }, 'id');
        } else {
            $message = trim((string) $data['message_note']);
            $recipients = $this->resolveBroadcastRecipients($targetType, $classId, $section);

            if ($recipients->isEmpty()) {
                return back()->withErrors(['target_type' => 'Secili hedef icin alici bulunamadi.'])->withInput();
            }

            $recipients->chunk(500)->each(function (Collection $chunk) use (&$links, &$skipped, $message) {
                foreach ($chunk as $receiver) {
                    $phone = $this->normalizePhoneForWhatsAppLink((string) $receiver->phone);
                    if ($phone === null) {
                        $skipped++;
                        continue;
                    }

                    $links[] = [
                        'receiver_name' => (string) ($receiver->name ?? ''),
                        'student_name' => null,
                        'phone' => '+' . $phone,
                        'message' => $message,
                        'wa_link' => $this->buildWhatsappDeepLink($phone, $message),
                        'pdf_link' => null,
                    ];
                }
            });
        }

        if (count($links) === 0) {
            return back()->withErrors(['student_ids' => 'Veli telefonu/eslesmesi bulunamadigi icin gonderim yapilamadi.'])->withInput();
        }

        return back()
            ->with('manual_links', $links)
            ->with('status', 'Hazir WhatsApp mesajlari olusturuldu. Tek tek veya toplu ac butonlari ile gonderebilirsiniz. Atlanan: ' . $skipped);
    }

    public function updateSettings(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'sender_phone' => 'nullable|string|max:30',
            'sender_phone_local' => 'nullable|string|max:20',
        ]);

        $senderInput = trim((string) ($data['sender_phone_local'] ?? ''));
        if ($senderInput !== '') {
            $senderInput = '+90'.$senderInput;
        } else {
            $senderInput = (string) ($data['sender_phone'] ?? '');
        }

        $senderPhone = $this->normalizeSenderPhone($senderInput);

        WhatsappSetting::query()->updateOrCreate(
            ['id' => 1],
            ['sender_phone' => $senderPhone]
        );

        if ($senderPhone === null) {
            return back()->with('status', 'Gonderilecek numara temizlendi.');
        }

        [$ok, $notes] = $this->ensureMessagingRuntimeReady($senderPhone);
        $status = $ok
            ? 'Gonderilecek numara guncellendi. Queue ve Venom servisi otomatik hazirlandi.'
            : 'Gonderilecek numara kaydedildi; otomatik baslatmada sorun var: ' . $notes;

        return back()->with('status', $status);
    }

    private function ensureMessagingRuntimeReady(string $senderPhone): array
    {
        $notes = [];
        $allOk = true;

        if (! $this->ensureQueueWorkerRunning()) {
            $allOk = false;
            $notes[] = 'queue worker baslatilamadi';
        }

        if (! $this->ensureVenomApiRunning()) {
            $allOk = false;
            $notes[] = 'Venom API baslatilamadi';
        }

        $session = $this->sessionFromPhone($senderPhone);
        if (! $this->startVenomSession($session, $notes)) {
            $allOk = false;
        }

        return [$allOk, implode('; ', $notes)];
    }

    private function ensureQueueWorkerRunning(): bool
    {
        if ($this->hasRunningProcess('artisan queue:work')) {
            return true;
        }

        $command = (string) config('services.venom.queue_command', '');
        $workdir = (string) config('services.venom.queue_workdir', base_path());
        if ($command === '') {
            return false;
        }

        return $this->startDetachedProcess($command, $workdir);
    }

    private function ensureVenomApiRunning(): bool
    {
        if ($this->isVenomReachable()) {
            return true;
        }

        $command = (string) config('services.venom.node_command', '');
        $workdir = (string) config('services.venom.node_workdir', '');
        if ($command === '') {
            return false;
        }

        if (! $this->startDetachedProcess($command, $workdir)) {
            return false;
        }

        for ($i = 0; $i < 15; $i++) {
            usleep(500000);
            if ($this->isVenomReachable()) {
                return true;
            }
        }

        return false;
    }

    private function isVenomReachable(): bool
    {
        $baseUrl = rtrim((string) config('services.venom.base_url', ''), '/');
        if ($baseUrl === '') {
            return false;
        }

        try {
            $response = Http::timeout(3)->get($baseUrl.'/api/default/qr');
            return $response->ok();
        } catch (\Throwable) {
            return false;
        }
    }

    private function startVenomSession(string $session, array &$notes): bool
    {
        $baseUrl = rtrim((string) config('services.venom.base_url', ''), '/');
        if ($baseUrl === '') {
            $notes[] = 'VENOM_BASE_URL bos';
            return false;
        }

        try {
            $response = Http::timeout(20)->post($baseUrl."/api/{$session}/start", []);
            if (! $response->ok()) {
                $notes[] = "session start HTTP {$response->status()}";
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            $notes[] = 'session start hata: '.$e->getMessage();
            return false;
        }
    }

    private function sessionFromPhone(string $senderPhone): string
    {
        $digits = preg_replace('/\D/', '', $senderPhone) ?? '';
        return $digits !== '' ? $digits : 'default';
    }

    private function hasRunningProcess(string $contains): bool
    {
        $needle = str_replace("'", "''", $contains);
        $script = "Get-CimInstance Win32_Process | Where-Object { \$_.CommandLine -like '*{$needle}*' } | Select-Object -First 1 -ExpandProperty ProcessId";
        $process = Process::fromShellCommandline('powershell -NoProfile -Command "'.$script.'"');
        $process->setTimeout(10);
        $process->run();

        if (! $process->isSuccessful()) {
            return false;
        }

        return trim($process->getOutput()) !== '';
    }

    private function startDetachedProcess(string $command, string $workdir): bool
    {
        try {
            $escapedWorkdir = str_replace('"', '`"', $workdir);
            $escapedCommand = str_replace('"', '`"', $command);
            $ps = 'Start-Process -FilePath "cmd.exe" -ArgumentList "/c '.$escapedCommand.'" -WorkingDirectory "'.$escapedWorkdir.'" -WindowStyle Hidden';

            $process = Process::fromShellCommandline('powershell -NoProfile -ExecutionPolicy Bypass -Command "'.$ps.'"');
            $process->setTimeout(15);
            $process->run();

            return $process->isSuccessful();
        } catch (\Throwable $e) {
            Log::error('WhatsApp detached process start failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function normalizeSenderPhone(string $value): ?string
    {
        $digits = preg_replace('/\D/', '', trim($value)) ?? '';
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '90' . substr($digits, 1);
        } elseif (! str_starts_with($digits, '90')) {
            $digits = '90' . $digits;
        }

        return strlen($digits) >= 12 ? '+' . $digits : null;
    }

    private function normalizePhoneForWhatsAppLink(string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', trim($phone)) ?? '';
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '90' . substr($digits, 1);
        } elseif (! str_starts_with($digits, '90')) {
            $digits = '90' . $digits;
        }

        return strlen($digits) >= 12 ? $digits : null;
    }

    private function buildWhatsappDeepLink(string $phoneDigits, string $message): string
    {
        return 'https://wa.me/' . $phoneDigits . '?text=' . rawurlencode($message);
    }

    private function buildParentReportMessage(string $template, string $parentName, string $studentName, string $note, string $pdfUrl): string
    {
        $message = str_replace(
            ['{veli_adi}', '{ogrenci_adi}', '{not}', '{pdf_link}'],
            [$parentName, $studentName, $note, $pdfUrl],
            $template
        );

        // WhatsApp'ta tiklanabilirlik icin URL tek basina satirda olmali.
        if ($pdfUrl !== '') {
            $escapedUrl = preg_quote($pdfUrl, '/');
            $message = preg_replace('/[ \t]*' . $escapedUrl . '[ \t]*/u', "\n".$pdfUrl."\n", $message) ?? $message;
        }

        $lines = array_map(static fn ($line) => rtrim($line), preg_split('/\R/u', $message) ?: []);
        $lines = array_values(array_filter($lines, static fn ($line) => $line !== ''));

        if ($pdfUrl !== '' && ! in_array($pdfUrl, $lines, true)) {
            $lines[] = $pdfUrl;
        }

        return implode("\n", $lines);
    }

    public function requeueQueued(): \Illuminate\Http\RedirectResponse
    {
        $logs = WhatsappLog::query()
            ->where('status', 'queued')
            ->orderBy('id')
            ->get(['id']);

        foreach ($logs as $log) {
            SendWhatsappMessageJob::dispatch((int) $log->id);
        }

        return back()->with('status', "Kuyruktaki {$logs->count()} kayit yeniden isleme alindi.");
    }
    private function resolveBroadcastRecipients(string $targetType, int $classId, string $section): Collection
    {
        if ($targetType === 'all_active') {
            return DB::table('users')
                ->where('is_active', true)
                ->whereNotNull('phone')
                ->where('phone', '<>', '')
                ->select('id', 'name', 'phone')
                ->orderBy('name')
                ->get();
        }

        if ($targetType === 'teachers') {
            $query = DB::table('users')
                ->join('user_roles', 'user_roles.user_id', '=', 'users.id')
                ->join('roles', 'roles.id', '=', 'user_roles.role_id')
                ->where('roles.name', 'teacher')
                ->where('users.is_active', true)
                ->whereNotNull('users.phone')
                ->where('users.phone', '<>', '')
                ->select('users.id', 'users.name', 'users.phone')
                ->distinct();

            if ($classId > 0 || $section !== '') {
                $query->join('class_user', 'class_user.user_id', '=', 'users.id')
                    ->join('classes', 'classes.id', '=', 'class_user.class_id');
                if ($classId > 0) {
                    $query->where('classes.id', $classId);
                }
                if ($section !== '') {
                    $query->where('classes.section', $section);
                }
            }

            return $query->orderBy('users.name')->get();
        }

        // parents
        $query = DB::table('users')
            ->join('user_roles', 'user_roles.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->join('parents', 'parents.user_id', '=', 'users.id')
            ->where('roles.name', 'parent')
            ->where('users.is_active', true)
            ->whereNotNull('users.phone')
            ->where('users.phone', '<>', '')
            ->select('users.id', 'users.name', 'users.phone')
            ->distinct();

        if ($classId > 0 || $section !== '') {
            $query->join('parent_student', 'parent_student.parent_id', '=', 'parents.id')
                ->join('students', 'students.id', '=', 'parent_student.student_id')
                ->join('classes', 'classes.id', '=', 'students.class_id');
            if ($classId > 0) {
                $query->where('classes.id', $classId);
            }
            if ($section !== '') {
                $query->where('classes.section', $section);
            }
        }

        return $query->orderBy('users.name')->get();
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
    private function nextDispatchDelay(array &$state): int
    {
        $state['count']++;
        if ($state['count'] === 1) {
            $state['delay'] = 20;
            return $state['delay'];
        }

        $state['delay'] += random_int(15, 25);

        if ($state['count'] % 10 === 0) {
            $state['delay'] += random_int(45, 55);
        }

        return $state['delay'];
    }
}


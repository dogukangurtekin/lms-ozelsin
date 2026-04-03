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
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
        $examRoomClasses = $classes
            ->pluck('name')
            ->filter(fn($name) => is_string($name) && trim($name) !== '')
            ->values();

        $maxPeriod = (int) (TeacherSchedule::query()->max('period_no') ?? 0);
        $lessonCountOptions = collect(range(1, max(5, $maxPeriod)))->values();

        $reportFieldOptions = [
            'student_number' => 'Numara',
            'name' => 'Ad Soyad',
            'email' => 'E-posta',
            'class_name' => 'SÃ„Â±nÃ„Â±f',
            'is_active' => 'Durum',
            'total_submissions' => 'Teslim SayÃ„Â±sÃ„Â±',
            'avg_score' => 'Ortalama Puan',
            'birth_date' => 'DoÃ„Å¸um Tarihi',
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
            'examRoomClasses',
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

    public function examEntryPackage(Request $request)
    {
        $this->prepareExamPdfRuntime();

        $data = $request->validate([
            'exam_date' => ['required', 'date'],
            'exam_time' => ['nullable', 'string', 'max:50'],
            'exam_address' => ['nullable', 'string', 'max:500'],
            'school_name' => ['nullable', 'string', 'max:255'],
            'exam_notes' => ['nullable', 'string', 'max:2000'],
            'session_one_title' => ['nullable', 'string', 'max:200'],
            'session_one_subjects' => ['nullable', 'string', 'max:4000'],
            'session_two_title' => ['nullable', 'string', 'max:200'],
            'session_two_subjects' => ['nullable', 'string', 'max:4000'],
            'name_column' => ['nullable', 'string', 'max:100'],
            'room_definitions' => ['required', 'string', 'max:5000'],
            'import_file' => ['nullable', 'file', 'max:5120'],
            'parsed_rows_json' => ['nullable', 'string'],
            'logo_payloads' => ['nullable', 'array'],
            'logo_payloads.*' => ['nullable', 'string', 'max:10485760'],
            'logo_files' => ['nullable', 'array'],
            'logo_files.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'exam_template' => ['nullable', 'in:modern,classic,minimal,grid,premium'],
            'theme_primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_accent_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_border_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $students = $this->extractStudentNamesFromRequest(
            request: $request,
            preferredNameColumn: (string) ($data['name_column'] ?? 'ad_soyad')
        );

        if (count($students) === 0) {
            return back()->withErrors([
                'import_file' => 'Excel dosyasindan ogrenci adi soyadi okunamadi. Ilk satir baslik icermeli ve en az bir dolu ad soyad satiri bulunmali.',
            ])->withInput();
        }

        $rooms = $this->parseRoomDefinitions((string) $data['room_definitions']);
        if (count($rooms) === 0) {
            return back()->withErrors([
                'room_definitions' => 'Salon listesi okunamadi. Her satiri "Salon|Kapasite" formatinda girin. Ornek: 7-A|18',
            ])->withInput();
        }

        $totalCapacity = collect($rooms)->sum('capacity');
        if ($totalCapacity < count($students)) {
            return back()->withErrors([
                'room_definitions' => "Toplam kapasite yetersiz. Ogrenci sayisi: ".count($students).", toplam kapasite: {$totalCapacity}.",
            ])->withInput();
        }

        $placements = $this->buildExamPlacements($students, $rooms);
        $generatedAt = now();
        $baseLogos = $this->resolveExamLogos();
        $payloadLogos = $this->extractLogoPayloadDataUris($request);
        $fileLogos = $this->extractLogoFileDataUris($request);
        $logos = [
            'primary' => $payloadLogos['primary'] ?? $fileLogos['primary'] ?? $baseLogos['primary'],
            'secondary' => $payloadLogos['secondary'] ?? $fileLogos['secondary'] ?? $baseLogos['secondary'],
        ];
        // Iki logo da ayni olmasin; ikincisi bossa birincinin kopyasini verme.
        if (($logos['secondary'] ?? null) === ($logos['primary'] ?? null)) {
            $logos['secondary'] = $baseLogos['secondary'] !== $logos['primary'] ? $baseLogos['secondary'] : null;
        }
        $sessionOneRows = $this->parseSessionSubjects((string) ($data['session_one_subjects'] ?? ''));
        $sessionTwoRows = $this->parseSessionSubjects((string) ($data['session_two_subjects'] ?? ''));

        $examAddress = trim((string) ($data['exam_address'] ?? ''));

        $examPayload = [
            'date' => Carbon::parse($data['exam_date']),
            'time' => $data['exam_time'] ?? null,
            'school_name' => $data['school_name'] ?? config('app.name', 'Ozelsin'),
            'address' => $examAddress !== '' ? $examAddress : null,
            'notes' => $data['exam_notes'] ?? null,
            'session_one_title' => $data['session_one_title'] ?? 'BÄ°RÄ°NCÄ° OTURUM - SÃ–ZEL ALAN',
            'session_one_rows' => $sessionOneRows,
            'session_two_title' => $data['session_two_title'] ?? 'Ä°KÄ°NCÄ° OTURUM - SAYISAL ALAN',
            'session_two_rows' => $sessionTwoRows,
            'template' => $data['exam_template'] ?? 'modern',
            'theme' => [
                'primary' => $data['theme_primary_color'] ?? '#0f172a',
                'accent' => $data['theme_accent_color'] ?? '#1d4ed8',
                'border' => $data['theme_border_color'] ?? '#cbd5e1',
            ],
        ];

        $safeName = 'sinav-giris-belgesi-'.now()->format('Ymd-His');
        $pdfFiles = [];
        foreach ($this->renderExamPlacementPdfChunks($placements, $examPayload, $generatedAt, $logos['primary'], $logos['secondary']) as $index => $pdfBinary) {
            $suffix = count($placements) > 40 ? '-part-'.($index + 1) : '';
            $pdfFiles[] = [
                'name' => 'sinav-giris-belgeleri'.$suffix.'.pdf',
                'content' => $pdfBinary,
            ];
        }

        $archive = $this->buildExamArchive(
            safeName: $safeName,
            xlsxContent: $this->buildExamPlacementXlsx($placements, $data, $generatedAt),
            pdfFiles: $pdfFiles
        );

        return response()->download(
            $archive['path'],
            $archive['download_name'],
            ['Content-Type' => $archive['content_type']]
        )->deleteFileAfterSend(true);
    }

    private function extractStudentNamesFromRequest(Request $request, string $preferredNameColumn): array
    {
        $rows = $this->extractRowsFromRequest($request);
        $normalizedColumn = $this->normalizeHeader($preferredNameColumn);
        $names = [];

        foreach ($rows as $row) {
            $normalizedRow = $this->normalizeRowKeys($row);
            $candidate = $normalizedRow[$normalizedColumn] ?? null;

            if (! is_string($candidate) || trim($candidate) === '') {
                $candidate = collect($normalizedRow)
                    ->first(fn($value) => is_string($value) && trim($value) !== '');
            }

            $candidate = trim($this->normalizeUtf8Text((string) $candidate));
            $candidate = $this->repairLikelyTurkishName($candidate);
            if ($candidate === '') {
                continue;
            }

            $names[] = preg_replace('/\s+/u', ' ', $candidate) ?: $candidate;
        }

        return array_values(array_unique($names));
    }

    private function parseRoomDefinitions(string $input): array
    {
        $rooms = [];
        $lines = preg_split('/\r\n|\n|\r/', trim($input)) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = preg_split('/\s*[|;,]\s*/', $line) ?: [];
            if (count($parts) < 2) {
                continue;
            }

            $roomName = trim((string) $parts[0]);
            $capacity = (int) preg_replace('/[^\d]/', '', (string) $parts[1]);

            if ($roomName === '' || $capacity <= 0) {
                continue;
            }

            $rooms[] = [
                'name' => $roomName,
                'capacity' => $capacity,
            ];
        }

        return $rooms;
    }

    private function buildExamPlacements(array $students, array $rooms): array
    {
        $shuffledStudents = collect($students)->shuffle()->values();
        $placements = [];
        $studentIndex = 0;

        foreach ($rooms as $room) {
            for ($seat = 1; $seat <= $room['capacity']; $seat++) {
                if (! isset($shuffledStudents[$studentIndex])) {
                    break 2;
                }

                $placements[] = [
                    'student_name' => $shuffledStudents[$studentIndex],
                    'room_name' => $room['name'],
                    'seat_number' => $seat,
                    'document_number' => $studentIndex + 1,
                ];

                $studentIndex++;
            }
        }

        return $placements;
    }

    private function buildExamPlacementXlsx(array $placements, array $data, Carbon $generatedAt): string
    {
        $generalRows = [
            ['Kurum', $data['school_name'] ?? config('app.name', 'Ozelsin')],
            ['Belge', 'Sinav Giris Belgesi'],
            ['Tarih', Carbon::parse($data['exam_date'])->format('d.m.Y')],
            ['Saat', $data['exam_time'] ?? '-'],
            ['Adres', $data['exam_address'] ?? '-'],
            ['Olusturma', $generatedAt->format('d.m.Y H:i')],
            [],
            ['Ogrenci Adi Soyadi', 'Sinif', 'Sube', 'Sira Numarasi'],
        ];

        foreach ($placements as $placement) {
            [$className, $section] = $this->splitRoomName((string) ($placement['room_name'] ?? ''));
            $generalRows[] = [
                (string) ($placement['student_name'] ?? ''),
                $className,
                $section,
                (string) ($placement['seat_number'] ?? ''),
            ];
        }

        $classGroupedRows = [['Sinif', 'Sube', 'Sira', 'Ogrenci Adi Soyadi']];
        $grouped = collect($placements)->groupBy(fn($p) => (string) ($p['room_name'] ?? '-'));
        foreach ($grouped as $roomName => $items) {
            [$className, $section] = $this->splitRoomName((string) $roomName);
            foreach ($items as $placement) {
                $classGroupedRows[] = [
                    $className,
                    $section,
                    (string) ($placement['seat_number'] ?? ''),
                    (string) ($placement['student_name'] ?? ''),
                ];
            }
            $classGroupedRows[] = ['', '', '', ''];
        }

        return $this->buildMinimalXlsxBinary([
            'Genel Liste' => $generalRows,
            'Sinif Bazli Liste' => $classGroupedRows,
        ]);
    }

    private function splitRoomName(string $roomName): array
    {
        $roomName = trim($roomName);
        if ($roomName === '') {
            return ['-', '-'];
        }
        $parts = preg_split('/[\\/\\-]/u', $roomName, 2) ?: [];
        if (count($parts) >= 2) {
            return [trim((string) $parts[0]), trim((string) $parts[1])];
        }
        return [$roomName, '-'];
    }

    private function buildMinimalXlsxBinary(array $sheets): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx-');
        if ($tmp === false) {
            throw new \RuntimeException('Gecici xlsx dosyasi olusturulamadi.');
        }
        $zip = new \ZipArchive();
        if ($zip->open($tmp, \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('XLSX zip acilamadi.');
        }

        $sheetNames = array_keys($sheets);
        $sheetCount = count($sheetNames);
        $xmlEscape = fn($v) => htmlspecialchars((string) $v, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $workbookSheetsXml = '';
        $workbookRelsXml = '';
        $contentTypeOverrides = '';

        $idx = 1;
        foreach ($sheetNames as $name) {
            $safe = mb_substr((string) $name, 0, 31);
            $workbookSheetsXml .= '<sheet name="'.$xmlEscape($safe).'" sheetId="'.$idx.'" r:id="rId'.$idx.'"/>';
            $workbookRelsXml .= '<Relationship Id="rId'.$idx.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$idx.'.xml"/>';
            $contentTypeOverrides .= '<Override PartName="/xl/worksheets/sheet'.$idx.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $idx++;
        }

        $zip->addFromString('[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            .$contentTypeOverrides
            .'</Types>'
        );
        $zip->addFromString('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            .'</Relationships>'
        );
        $zip->addFromString('docProps/core.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<dc:title>SÄ±nav Oturma PlanÄ±</dc:title><dc:creator>lms-ozelsin</dc:creator></cp:coreProperties>'
        );
        $zip->addFromString('docProps/app.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>lms-ozelsin</Application></Properties>'
        );
        $zip->addFromString('xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>'
            .$workbookSheetsXml
            .'</sheets></workbook>'
        );
        $zip->addFromString('xl/_rels/workbook.xml.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .$workbookRelsXml
            .'<Relationship Id="rId'.($sheetCount + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>'
        );
        $zip->addFromString('xl/styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts><fills count="1"><fill><patternFill patternType="none"/></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="1"><xf xfId="0"/></cellXfs></styleSheet>'
        );

        $toCol = function(int $index): string {
            $result = '';
            while ($index > 0) {
                $index--;
                $result = chr(65 + ($index % 26)).$result;
                $index = intdiv($index, 26);
            }
            return $result;
        };

        $sheetNo = 1;
        foreach ($sheets as $rows) {
            $sheetData = '';
            $r = 1;
            foreach ((array) $rows as $row) {
                $cellsXml = '';
                $c = 1;
                foreach ((array) $row as $value) {
                    $ref = $toCol($c).$r;
                    if (is_numeric($value) && $value !== '') {
                        $cellsXml .= '<c r="'.$ref.'"><v>'.$xmlEscape((string) $value).'</v></c>';
                    } else {
                        $cellsXml .= '<c r="'.$ref.'" t="inlineStr"><is><t>'.$xmlEscape((string) $value).'</t></is></c>';
                    }
                    $c++;
                }
                $sheetData .= '<row r="'.$r.'">'.$cellsXml.'</row>';
                $r++;
            }
            $zip->addFromString('xl/worksheets/sheet'.$sheetNo.'.xml',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'
                .$sheetData
                .'</sheetData></worksheet>'
            );
            $sheetNo++;
        }

        $zip->close();
        $binary = file_get_contents($tmp) ?: '';
        @unlink($tmp);
        return $binary;
    }

    private function resolveExamLogos(): array
    {
        $primaryPaths = [
            base_path('logo.png'),
            public_path('assets/logo.png'),
        ];
        $secondaryImagePaths = [
            base_path('mutlukent.png'),
            public_path('assets/mutlukent.png'),
        ];

        $primary = null;
        foreach ($primaryPaths as $path) {
            if (! is_string($path) || ! is_file($path)) {
                continue;
            }
            $content = file_get_contents($path);
            if ($content === false) {
                continue;
            }
            $primary = 'data:image/png;base64,'.base64_encode($content);
            break;
        }

        $secondary = null;
        foreach ($secondaryImagePaths as $path) {
            if (! is_string($path) || ! is_file($path)) {
                continue;
            }
            $content = file_get_contents($path);
            if ($content === false) {
                continue;
            }
            $secondary = 'data:image/png;base64,'.base64_encode($content);
            break;
        }

        $pdfPath = base_path('LOGO.pdf');
        if ($secondary === null && is_file($pdfPath) && extension_loaded('imagick')) {
            try {
                $img = new \Imagick();
                $img->setResolution(144, 144);
                $img->readImage($pdfPath.'[0]');
                $img->setImageFormat('png');
                $secondary = 'data:image/png;base64,'.base64_encode((string) $img->getImageBlob());
                $img->clear();
                $img->destroy();
            } catch (\Throwable $e) {
                $secondary = null;
            }
        }

        // LOGO.pdf rasterize edilemezse (Imagick yoksa) ikinci logo yine boÃ…Å¸ kalmasÃ„Â±n.
        if ($secondary === null) {
            $secondary = $primary;
        }

        return [
            'primary' => $primary,
            'secondary' => $secondary,
        ];
    }

    private function extractUploadedLogoDataUris(Request $request): array
    {
        $images = $request->file('attachment_images', []);
        $uris = [];

        if (! is_array($images)) {
            $images = [$images];
        }

        foreach ($images as $image) {
            if (! $image instanceof UploadedFile || ! $image->isValid()) {
                continue;
            }
            $mime = (string) ($image->getMimeType() ?: 'image/png');
            if (! str_starts_with($mime, 'image/')) {
                continue;
            }
            $content = @file_get_contents($image->getRealPath());
            if ($content === false || $content === '') {
                continue;
            }
            $uris[] = 'data:'.$mime.';base64,'.base64_encode($content);
            if (count($uris) >= 2) {
                break;
            }
        }

        return [
            'primary' => $uris[0] ?? null,
            'secondary' => $uris[1] ?? null,
        ];
    }

    private function extractLogoPayloadDataUris(Request $request): array
    {
        $payloads = $request->input('logo_payloads', []);
        if (! is_array($payloads)) {
            $payloads = [$payloads];
        }

        $uris = [];
        foreach ($payloads as $payload) {
            if (! is_string($payload)) {
                continue;
            }
            $payload = trim($payload);
            if ($payload === '') {
                continue;
            }
            $payload = preg_replace('/\s+/', '', $payload) ?? $payload;
            if (! preg_match('/^data:image\/(png|jpeg|jpg|webp);base64,([A-Za-z0-9+\/=]+)$/i', $payload, $matches)) {
                continue;
            }
            $decoded = base64_decode((string) ($matches[2] ?? ''), true);
            if (! is_string($decoded) || $decoded === '') {
                continue;
            }
            if (@getimagesizefromstring($decoded) === false) {
                continue;
            }
            $uris[] = $payload;
            if (count($uris) >= 2) {
                break;
            }
        }

        return [
            'primary' => $uris[0] ?? null,
            'secondary' => $uris[1] ?? null,
        ];
    }

    private function extractLogoFileDataUris(Request $request): array
    {
        $files = $request->file('logo_files', []);
        if (! is_array($files)) {
            $files = [$files];
        }

        $uris = [];
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }
            $mime = (string) ($file->getMimeType() ?: 'image/png');
            if (! str_starts_with($mime, 'image/')) {
                continue;
            }
            $content = @file_get_contents($file->getRealPath());
            if (! is_string($content) || $content === '') {
                continue;
            }
            if (@getimagesizefromstring($content) === false) {
                continue;
            }
            $uris[] = 'data:'.$mime.';base64,'.base64_encode($content);
            if (count($uris) >= 2) {
                break;
            }
        }

        return [
            'primary' => $uris[0] ?? null,
            'secondary' => $uris[1] ?? null,
        ];
    }

    private function parseSessionSubjects(string $raw): array
    {
        $rows = [];
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }
            $parts = array_map('trim', explode('|', $line));
            $name = $this->normalizeUtf8Text((string) ($parts[0] ?? ''));
            if ($name === '') {
                continue;
            }
            $count = (int) preg_replace('/\D+/', '', (string) ($parts[1] ?? '0'));
            $rows[] = ['name' => $name, 'count' => max(0, $count)];
        }
        return $rows;
    }

    private function normalizeUtf8Text(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        $value = str_replace(
            ['Ã„Â°', 'Ã„Â±', 'Ãƒâ€¡', 'ÃƒÂ§', 'Ãƒâ€“', 'ÃƒÂ¶', 'ÃƒÅ“', 'ÃƒÂ¼', 'Ã„Å¾', 'Ã„Å¸', 'Ã…Å¾', 'Ã…Å¸'],
            ['Ä°', 'Ä±', 'Ã‡', 'Ã§', 'Ã–', 'Ã¶', 'Ãœ', 'Ã¼', 'Ä', 'ÄŸ', 'Å', 'ÅŸ'],
            $value
        );
        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }
        $converted = @mb_convert_encoding($value, 'UTF-8', 'ISO-8859-9,Windows-1254,ISO-8859-1,Windows-1252');
        if (is_string($converted) && $converted !== '') {
            return $converted;
        }
        $fallback = @iconv('Windows-1254', 'UTF-8//IGNORE', $value);
        return is_string($fallback) && $fallback !== '' ? $fallback : $value;
    }

    private function repairLikelyTurkishName(string $value): string
    {
        if ($value === '' || ! str_contains($value, '?')) {
            return $value;
        }

        $repaired = preg_replace('/(?<=\p{Lu})\?(?=\p{Lu})/u', 'Ä°', $value);
        if (is_string($repaired) && $repaired !== '') {
            return $repaired;
        }

        return str_replace('?', 'Ä°', $value);
    }

    private function renderExamPlacementPdfChunks(array $placements, array $exam, Carbon $generatedAt, ?string $logoDataUri, ?string $secondaryLogoDataUri): array
    {
        $chunkSize = 40;
        $chunks = array_chunk($placements, $chunkSize);
        $outputs = [];

        foreach ($chunks as $chunk) {
            $pdfHtml = view('reports.pdf.exam-entry-cards', [
                'placements' => $chunk,
                'exam' => $exam,
                'generatedAt' => $generatedAt,
                'logoDataUri' => $logoDataUri,
                'secondaryLogoDataUri' => $secondaryLogoDataUri,
            ])->render();

            $options = new Options();
            $options->set('isRemoteEnabled', false);
            $options->set('isHtml5ParserEnabled', true);
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($pdfHtml, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $outputs[] = $dompdf->output();

            unset($dompdf, $pdfHtml);
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }

        return $outputs;
    }

    private function prepareExamPdfRuntime(): void
    {
        @ini_set('memory_limit', '768M');
        @set_time_limit(600);
    }

    private function buildExamArchive(string $safeName, string $xlsxContent, array $pdfFiles): array
    {
        if (class_exists(\ZipArchive::class)) {
            $zipPath = tempnam(sys_get_temp_dir(), 'exam-entry-');
            $zip = new \ZipArchive();

            if ($zipPath === false || $zip->open($zipPath, \ZipArchive::OVERWRITE) !== true) {
                abort(500, 'Zip dosyasi olusturulamadi.');
            }

            foreach ($pdfFiles as $pdfFile) {
                $zip->addFromString($pdfFile['name'], $pdfFile['content']);
            }

            $zip->addFromString('sinav-oturma-plani.xlsx', $xlsxContent);
            $zip->close();

            return [
                'path' => $zipPath,
                'download_name' => $safeName.'-paketi.zip',
                'content_type' => 'application/zip',
            ];
        }

        if (! class_exists(\PharData::class)) {
            abort(500, 'Sunucuda ne ZipArchive ne de PharData mevcut. Paket olusturulamiyor.');
        }

        $baseTarPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('exam-entry-', true).'.tar';
        $archive = new \PharData($baseTarPath);

        foreach ($pdfFiles as $pdfFile) {
            $archive->addFromString($pdfFile['name'], $pdfFile['content']);
        }

        $archive->addFromString('sinav-oturma-plani.xlsx', $xlsxContent);
        $archive->compress(\Phar::GZ);

        unset($archive);

        @unlink($baseTarPath);

        return [
            'path' => $baseTarPath.'.gz',
            'download_name' => $safeName.'-paketi.tar.gz',
            'content_type' => 'application/gzip',
        ];
    }

    private function parseImportFile(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $content = file_get_contents($file->getRealPath()) ?: '';

        if (in_array($ext, ['csv', 'txt'], true)) {
            return $this->parseDelimitedContent($content);
        }

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
        $headers = array_map(fn($h) => $this->normalizeHeader($this->normalizeUtf8Text((string) $h)), str_getcsv($lines[0], $delimiter));
        $rows = [];

        foreach (array_slice($lines, 1) as $line) {
            if (trim($line) === '') {
                continue;
            }

            $values = str_getcsv($line, $delimiter);
            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = isset($values[$index]) ? $this->normalizeUtf8Text((string) $values[$index]) : null;
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
            $cells = array_map(function ($cell) {
                $decoded = html_entity_decode($cell, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return $this->normalizeUtf8Text(trim(strip_tags($decoded)));
            }, $cellMatches[1]);

            if ($trIndex === 0) {
                $headers = array_map(fn($h) => $this->normalizeHeader($this->normalizeUtf8Text((string) $h)), $cells);
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
        foreach ($delimiters as $delimiter) {
            $scores[$delimiter] = count(str_getcsv($line, $delimiter));
        }

        arsort($scores);

        return (string) array_key_first($scores);
    }

    private function extractRowsFromRequest(Request $request): array
    {
        $jsonRows = $request->input('parsed_rows_json');
        if (is_string($jsonRows) && $jsonRows !== '') {
            $decoded = json_decode($jsonRows, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map(
                    fn($row) => is_array($row) ? $this->normalizeRowKeys($row) : null,
                    $decoded
                )));
            }
        }

        /** @var UploadedFile|null $file */
        $file = $request->file('import_file');
        if (! $file instanceof UploadedFile) {
            return [];
        }

        return $this->parseImportFile($file);
    }

    private function normalizeHeader(string $header): string
    {
        $h = preg_replace('/^\xEF\xBB\xBF/u', '', $header) ?? $header;
        $h = Str::lower(trim($this->normalizeUtf8Text($h)));
        $h = str_replace(['ÃƒÂ§', 'Ã„Å¸', 'Ã„Â±', 'Ã„Â°', 'ÃƒÂ¶', 'Ã…Å¸', 'ÃƒÂ¼'], ['c', 'g', 'i', 'i', 'o', 's', 'u'], $h);
        $h = str_replace([' ', '-', '/'], '_', $h);
        $h = preg_replace('/_+/', '_', $h) ?? $h;

        return trim($h, "_ \t\n\r\0\x0B");
    }

    private function normalizeRowKeys(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalizedKey = $this->normalizeHeader($this->normalizeUtf8Text((string) $key));
            $normalized[$normalizedKey] = is_string($value)
                ? $this->normalizeUtf8Text($value)
                : $value;
        }

        return $normalized;
    }
}




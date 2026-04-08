<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Role;
use App\Models\ParentProfile;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserManagementController extends Controller
{
    private function resolveRoleId(string $key, string $defaultLabel): int
    {
        $role = Role::query()->where('name', $key)->first();
        if (! $role) {
            $role = Role::query()
                ->whereRaw('LOWER(label) = ?', [mb_strtolower($defaultLabel)])
                ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($defaultLabel)])
                ->first();
        }

        if (! $role) {
            $role = Role::query()->firstOrCreate(
                ['name' => $key],
                ['label' => $defaultLabel]
            );
        }

        return (int) $role->id;
    }

    public function index()
    {
        $hasSectionColumn = Schema::hasColumn('classes', 'section');
        $hasHomeroomTeacherColumn = Schema::hasColumn('classes', 'homeroom_teacher_id');

        $users = User::with('roles')->latest()->paginate(12);
        $roles = Role::all();
        $classesQuery = SchoolClass::query()->orderBy('grade_level');
        if ($hasSectionColumn) {
            $classesQuery->orderBy('section');
        }
        $classes = $hasHomeroomTeacherColumn
            ? $classesQuery->with('homeroomTeacher:id,name')->get()
            : $classesQuery->get();
        $teacherUsers = User::query()
            ->where(function ($query) {
                $query->whereHas('roles', fn($q) => $q->where('name', 'teacher'))
                    ->orWhereIn('users.id', Teacher::query()->select('user_id'));
            })
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
        $classSelect = $hasSectionColumn ? 'id,name,grade_level,section' : 'id,name,grade_level';
        $students = Student::with('user:id,name,email,phone,is_active', 'class:'.$classSelect)
            ->latest()
            ->get();
        $teachers = Teacher::with('user:id,name,email,phone,is_active', 'user.classes:id,name')
            ->latest()
            ->get();
        $branches = Schema::hasTable('branches')
            ? Branch::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        $summary = [
            'total_students' => User::whereHas('roles', fn($q) => $q->where('name', 'student'))->count(),
            'active_students' => User::whereHas('roles', fn($q) => $q->where('name', 'student'))->where('is_active', true)->count(),
            'total_teachers' => User::whereHas('roles', fn($q) => $q->where('name', 'teacher'))->count(),
            'total_classes' => $classes->count(),
        ];

        $genderData = [
            'active' => $summary['active_students'],
            'passive' => max($summary['total_students'] - $summary['active_students'], 0),
        ];

        $classStudentCounts = Student::query()
            ->select('class_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('class_id')
            ->groupBy('class_id')
            ->pluck('total', 'class_id');

        $classCapacities = $classes->map(function (SchoolClass $class) use ($classStudentCounts) {
            $total = (int) ($classStudentCounts[$class->id] ?? 0);
            return ['name' => $class->name, 'grade_level' => $class->grade_level, 'student_count' => $total];
        });

        $gradeDistribution = $classCapacities
            ->groupBy('grade_level')
            ->map(function ($items, $grade) use ($summary) {
                $count = (int) collect($items)->sum('student_count');
                $percent = $summary['total_students'] > 0
                    ? (int) round(($count / $summary['total_students']) * 100)
                    : 0;

                return [
                    'grade' => (string) $grade,
                    'count' => $count,
                    'percent' => $percent,
                ];
            })
            ->sortBy('grade')
            ->values();

        $studentTableQuery = Student::query()
            ->select('students.*', 'users.name', 'users.email', 'users.phone', 'users.is_active', 'classes.name as class_name', 'classes.grade_level')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->leftJoin('classes', 'classes.id', '=', 'students.class_id')
            ->orderBy('users.name');
        if ($hasSectionColumn) {
            $studentTableQuery->addSelect('classes.section');
        }
        $studentTable = $studentTableQuery->get();

        $teacherTable = User::query()
            ->select(
                'users.id as user_id',
                'users.name',
                'users.email',
                'users.phone',
                'users.is_active',
                'teachers.id as teacher_profile_id',
                'teachers.branch'
            )
            ->join('teachers', 'teachers.user_id', '=', 'users.id')
            ->orderBy('users.name')
            ->get();

        $teacherClassNamesMap = DB::table('class_user')
            ->join('classes', 'classes.id', '=', 'class_user.class_id')
            ->whereIn('class_user.user_id', $teacherTable->pluck('user_id')->all())
            ->select('class_user.user_id', DB::raw('GROUP_CONCAT(classes.name ORDER BY classes.name SEPARATOR ", ") as class_names'))
            ->groupBy('class_user.user_id')
            ->pluck('class_names', 'class_user.user_id');

        $teacherTable = $teacherTable->map(function ($teacher) use ($teacherClassNamesMap) {
            $teacher->class_names = (string) ($teacherClassNamesMap[$teacher->user_id] ?? '');
            return $teacher;
        });

        $parentTable = ParentProfile::query()
            ->select('parents.id', 'parents.relation_type', 'users.name', 'users.email', 'users.phone')
            ->join('users', 'users.id', '=', 'parents.user_id')
            ->orderBy('users.name')
            ->get();

        $parentStudentNamesMap = DB::table('parent_student')
            ->join('students', 'students.id', '=', 'parent_student.student_id')
            ->join('users as su', 'su.id', '=', 'students.user_id')
            ->whereIn('parent_student.parent_id', $parentTable->pluck('id')->all())
            ->select('parent_student.parent_id', DB::raw('GROUP_CONCAT(su.name ORDER BY su.name SEPARATOR ", ") as student_names'))
            ->groupBy('parent_student.parent_id')
            ->pluck('student_names', 'parent_student.parent_id');

        $parentTable = $parentTable->map(function ($parent) use ($parentStudentNamesMap) {
            $parent->student_names = (string) ($parentStudentNamesMap[$parent->id] ?? '');
            return $parent;
        });

        return view('users.index', compact(
            'users',
            'roles',
            'classes',
            'teacherUsers',
            'students',
            'teachers',
            'summary',
            'genderData',
            'classCapacities',
            'gradeDistribution',
            'studentTable',
            'teacherTable',
            'parentTable',
            'branches'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'is_active' => true,
        ]);

        $user->roles()->sync([$data['role_id']]);

        return back()->with('status', 'Kullanici olusturuldu.');
    }

    public function storeStudent(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'password' => 'required|string|min:8',
            'student_number' => 'required|string|max:50|unique:students,student_number',
            'birth_date' => 'nullable|date',
            'class_id' => 'nullable|exists:classes,id',
            'is_active' => 'nullable|boolean',
            'parent_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:30',
            'parent_email' => 'nullable|email|max:255',
            'parent_relation_type' => 'nullable|string|max:50',
        ]);

        $studentRoleId = $this->resolveRoleId('student', 'Öğrenci');

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        $user->roles()->sync([$studentRoleId]);

        $student = Student::create([
            'user_id' => $user->id,
            'class_id' => $data['class_id'] ?? null,
            'student_number' => $data['student_number'],
            'birth_date' => $data['birth_date'] ?? null,
        ]);

        if (! empty($data['class_id'])) {
            $user->classes()->syncWithoutDetaching([$data['class_id']]);
        }

        if (! empty($data['parent_name']) || ! empty($data['parent_phone']) || ! empty($data['parent_email'])) {
            $parentRoleId = $this->resolveRoleId('parent', 'Veli');

            $parentUser = null;
            if (! empty($data['parent_email'])) {
                $parentUser = User::where('email', $data['parent_email'])->first();
            }
            if (! $parentUser && ! empty($data['parent_phone'])) {
                $parentUser = User::where('phone', $data['parent_phone'])->first();
            }

            if (! $parentUser) {
                $generatedEmail = ! empty($data['parent_email'])
                    ? $data['parent_email']
                    : ('veli+'.now()->format('YmdHis').rand(100, 999).'@example.local');
                $parentUser = User::create([
                    'name' => $data['parent_name'] ?: 'Veli',
                    'email' => $generatedEmail,
                    'phone' => $data['parent_phone'] ?? null,
                    'password' => Hash::make('12345678'),
                    'is_active' => true,
                ]);
            }

            $parentUser->roles()->syncWithoutDetaching([$parentRoleId]);
            $parentUser->update([
                'name' => $data['parent_name'] ?: $parentUser->name,
                'phone' => $data['parent_phone'] ?: $parentUser->phone,
                'email' => (! empty($data['parent_email']) && str_ends_with((string) $parentUser->email, '@example.local'))
                    ? $data['parent_email']
                    : $parentUser->email,
            ]);

            $parentProfile = ParentProfile::firstOrCreate(
                ['user_id' => $parentUser->id],
                ['relation_type' => $data['parent_relation_type'] ?? 'Veli']
            );

            if (! empty($data['parent_relation_type']) && $parentProfile->relation_type !== $data['parent_relation_type']) {
                $parentProfile->update(['relation_type' => $data['parent_relation_type']]);
            }

            DB::table('parent_student')->updateOrInsert(
                ['parent_id' => $parentProfile->id, 'student_id' => $student->id],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        return redirect()->route('users.index', ['tab' => 'ogrenciler'])
            ->with('status', 'Öğrenci kaydı oluşturuldu: '.$student->student_number);
    }

    public function storeTeacher(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'password' => 'required|string|min:8',
            'branch' => 'nullable|string|max:255',
            'class_ids' => 'nullable|array',
            'class_ids.*' => 'exists:classes,id',
            'is_active' => 'nullable|boolean',
        ]);

        $branchName = null;
        if (! empty($data['branch'])) {
            $branch = Schema::hasTable('branches')
                ? Branch::firstOrCreate(['name' => trim((string) $data['branch'])])
                : null;
            $branchName = $branch?->name ?? trim((string) $data['branch']);
        }

        $teacherRoleId = $this->resolveRoleId('teacher', 'Öğretmen');

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        $user->roles()->sync([$teacherRoleId]);

        Teacher::create([
            'user_id' => $user->id,
            'branch' => $branchName,
        ]);

        if (! empty($data['class_ids'])) {
            $user->classes()->sync($data['class_ids']);
        }

        return redirect()->route('users.index', ['tab' => 'ogretmenler'])
            ->with('status', 'Öğretmen kaydı oluşturuldu.');
    }

    public function storeClass(Request $request)
    {
        $hasSectionColumn = Schema::hasColumn('classes', 'section');
        $hasHomeroomTeacherColumn = Schema::hasColumn('classes', 'homeroom_teacher_id');

        $data = $request->validate([
            'grade_level' => 'required|string|max:20',
            'section' => $hasSectionColumn ? 'required|string|max:10' : 'nullable|string|max:10',
            'description' => 'nullable|string|max:1000',
            'homeroom_teacher_id' => $hasHomeroomTeacherColumn ? 'nullable|exists:users,id' : 'nullable',
        ]);

        $gradeLevel = trim((string) $data['grade_level']);
        $section = strtoupper(trim((string) ($data['section'] ?? 'A')));
        $name = $gradeLevel.'-'.$section;
        $payload = [
            'name' => $name,
            'grade_level' => $gradeLevel,
            'description' => $data['description'] ?? null,
        ];
        if ($hasSectionColumn) {
            $payload['section'] = $section;
        }
        if ($hasHomeroomTeacherColumn) {
            $payload['homeroom_teacher_id'] = $data['homeroom_teacher_id'] ?? null;
        }

        $class = SchoolClass::create($payload);

        if ($hasHomeroomTeacherColumn && ! empty($data['homeroom_teacher_id'])) {
            DB::table('class_user')->updateOrInsert(
                ['class_id' => $class->id, 'user_id' => (int) $data['homeroom_teacher_id']],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        return back()->with('status', 'Sinif kaydi olusturuldu: '.$name);
    }

    public function importStudents(Request $request)
    {
        $rows = $this->extractRowsFromRequest($request);
        if (empty($rows)) {
            return $this->importFailureResponse($request, 'Dosya okunamadi veya satir bulunamadi. Lutfen sistemden indirilen sablonu kullanin.');
        }
        $created = 0;
        foreach ($rows as $row) {
            $row = $this->normalizeRowKeys($row);
            if (empty($row['name']) || empty($row['email']) || empty($row['password']) || empty($row['student_number'])) {
                continue;
            }

            if (User::where('email', $row['email'])->exists() || Student::where('student_number', $row['student_number'])->exists()) {
                continue;
            }

            $classId = null;
            if (! empty($row['class_name'])) {
                $classId = SchoolClass::where('name', $row['class_name'])->value('id');
            }

            $studentRoleId = Role::where('name', 'student')->value('id');
            if (! $studentRoleId) {
                continue;
            }

            $user = User::create([
                'name' => $row['name'],
                'email' => $row['email'],
                'phone' => $row['phone'] ?? null,
                'password' => Hash::make($row['password']),
                'is_active' => (bool) ($row['is_active'] ?? true),
            ]);
            $user->roles()->sync([$studentRoleId]);

            Student::create([
                'user_id' => $user->id,
                'class_id' => $classId,
                'student_number' => $row['student_number'],
                'birth_date' => $row['birth_date'] ?? null,
            ]);

            if ($classId) {
                $user->classes()->syncWithoutDetaching([$classId]);
            }
            $created++;
        }

        if ($created === 0) {
            return $this->importFailureResponse($request, 'Toplu ogrenci kaydinda yeni kayit eklenmedi. Alanlari ve tekrar eden email/numara degerlerini kontrol edin.');
        }

        return $this->importSuccessResponse($request, "Toplu ogrenci kaydi tamamlandi. Eklenen: {$created}", $created);
    }

    public function importTeachers(Request $request)
    {
        $rows = $this->extractRowsFromRequest($request);
        if (empty($rows)) {
            return $this->importFailureResponse($request, 'Dosya okunamadi veya satir bulunamadi. Lutfen sistemden indirilen sablonu kullanin.');
        }
        $created = 0;
        foreach ($rows as $row) {
            $row = $this->normalizeRowKeys($row);
            if (empty($row['name']) || empty($row['email']) || empty($row['password'])) {
                continue;
            }

            if (User::where('email', $row['email'])->exists()) {
                continue;
            }

            $teacherRoleId = Role::where('name', 'teacher')->value('id');
            if (! $teacherRoleId) {
                continue;
            }

            $user = User::create([
                'name' => $row['name'],
                'email' => $row['email'],
                'phone' => $row['phone'] ?? null,
                'password' => Hash::make($row['password']),
                'is_active' => (bool) ($row['is_active'] ?? true),
            ]);
            $user->roles()->sync([$teacherRoleId]);

            $branchName = null;
            if (! empty($row['branch'])) {
                $branch = Schema::hasTable('branches')
                    ? Branch::firstOrCreate(['name' => trim((string) $row['branch'])])
                    : null;
                $branchName = $branch?->name ?? trim((string) $row['branch']);
            }

            Teacher::create([
                'user_id' => $user->id,
                'branch' => $branchName,
            ]);

            if (! empty($row['class_names'])) {
                $classNames = array_map('trim', explode('|', $row['class_names']));
                $classIds = SchoolClass::whereIn('name', $classNames)->pluck('id')->all();
                if (! empty($classIds)) {
                    $user->classes()->sync($classIds);
                }
            }

            $created++;
        }

        if ($created === 0) {
            return $this->importFailureResponse($request, 'Toplu ogretmen kaydinda yeni kayit eklenmedi. Alanlari ve tekrar eden email degerlerini kontrol edin.');
        }

        return $this->importSuccessResponse($request, "Toplu ogretmen kaydi tamamlandi. Eklenen: {$created}", $created);
    }

    public function importClasses(Request $request)
    {
        $rows = $this->extractRowsFromRequest($request);
        $hasSectionColumn = Schema::hasColumn('classes', 'section');
        $hasHomeroomTeacherColumn = Schema::hasColumn('classes', 'homeroom_teacher_id');
        if (empty($rows)) {
            return $this->importFailureResponse($request, 'Dosya okunamadi veya satir bulunamadi. Lutfen sistemden indirilen sablonu kullanin.');
        }
        $created = 0;

        foreach ($rows as $row) {
            $row = $this->normalizeRowKeys($row);
            if (empty($row['grade_level']) || empty($row['section'])) {
                continue;
            }

            $grade = trim($row['grade_level']);
            $section = strtoupper(trim((string) $row['section']));
            $name = $grade.'-'.$section;
            if (SchoolClass::where('name', $name)->exists()) {
                continue;
            }

            $payload = [
                'name' => $name,
                'grade_level' => $grade,
                'description' => $row['description'] ?? null,
            ];
            if ($hasSectionColumn) {
                $payload['section'] = $section;
            }

            $teacherId = null;
            if (! empty($row['homeroom_teacher_email'])) {
                $teacherId = User::where('email', $row['homeroom_teacher_email'])->value('id');
            }
            if ($hasHomeroomTeacherColumn) {
                $payload['homeroom_teacher_id'] = $teacherId;
            }

            $class = SchoolClass::create($payload);
            if ($teacherId) {
                DB::table('class_user')->updateOrInsert(
                    ['class_id' => $class->id, 'user_id' => (int) $teacherId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
            $created++;
        }

        if ($created === 0) {
            return $this->importFailureResponse($request, 'Toplu sinif kaydinda yeni kayit eklenmedi. Sinif seviyesi/sube alanlarini ve tekrar eden sinif adlarini kontrol edin.');
        }

        return $this->importSuccessResponse($request, "Toplu sinif kaydi tamamlandi. Eklenen: {$created}", $created);
    }

    public function storeBranch(Request $request)
    {
        abort_unless(Schema::hasTable('branches'), 500, 'Brans tablosu bulunamadi. Lutfen migration calistirin.');

        $data = $request->validate([
            'name' => 'required|string|max:255|unique:branches,name',
        ]);

        Branch::create([
            'name' => trim((string) $data['name']),
        ]);

        return redirect()->route('users.index', ['tab' => 'branslar'])->with('status', 'Brans kaydedildi.');
    }

    public function importBranches(Request $request)
    {
        abort_unless(Schema::hasTable('branches'), 500, 'Brans tablosu bulunamadi. Lutfen migration calistirin.');

        $rows = $this->extractRowsFromRequest($request);
        if (empty($rows)) {
            return $this->importFailureResponse($request, 'Dosya okunamadi veya satir bulunamadi. Lutfen sistemden indirilen sablonu kullanin.');
        }

        $created = 0;
        foreach ($rows as $row) {
            $row = $this->normalizeRowKeys($row);
            $name = trim((string) ($row['name'] ?? $row['branch'] ?? ''));
            if ($name === '') {
                continue;
            }

            $branch = Branch::firstOrCreate(['name' => $name]);
            if ($branch->wasRecentlyCreated) {
                $created++;
            }
        }

        if ($created === 0) {
            return $this->importFailureResponse($request, 'Toplu brans kaydinda yeni kayit eklenmedi. Tekrar eden veya bos satirlari kontrol edin.');
        }

        return $this->importSuccessResponse($request, "Toplu brans kaydi tamamlandi. Eklenen: {$created}", $created);
    }

    public function downloadTemplate(string $type): StreamedResponse
    {
        $templates = [
            'students' => [
                ['ad_soyad', 'e_posta', 'telefon', 'sifre', 'ogrenci_numarasi', 'dogum_tarihi', 'sinif_adi', 'aktif_mi'],
                ['Ali Yılmaz', 'ali@example.com', '5551112233', '12345678', 'STU-1001', '2012-04-18', '5-A', '1'],
            ],
            'teachers' => [
                ['ad_soyad', 'e_posta', 'telefon', 'sifre', 'brans', 'siniflar', 'aktif_mi'],
                ['Ayşe Kaya', 'ayse@example.com', '5552223344', '12345678', 'Matematik', '5-A|5-B', '1'],
            ],
            'classes' => [
                ['sinif_seviyesi', 'sube', 'aciklama', 'sube_ogretmeni_e_posta'],
                ['5', 'A', 'LGS Hazırlık', 'ayse@example.com'],
            ],
            'branches' => [
                ['brans_adi'],
                ['Matematik'],
            ],
        ];

        abort_unless(isset($templates[$type]), 404);
        $rows = $templates[$type];
        $filename = "{$type}_template.xls";

        return response()->streamDownload(function () use ($rows) {
            foreach ($rows as $row) {
                echo implode("\t", $row)."\r\n";
            }
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    private function parseImportFile(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $content = file_get_contents($file->getRealPath()) ?: '';

        if (in_array($ext, ['csv', 'txt'], true)) {
            return $this->parseDelimitedContent($content);
        }

        // xls/xlsx accepted: plain text/tab-delimited and html table fallback.
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
        $headers = array_map(fn($h) => $this->normalizeHeader((string) $h), str_getcsv($lines[0], $delimiter));
        $rows = [];

        foreach (array_slice($lines, 1) as $line) {
            if (trim($line) === '') {
                continue;
            }
            $values = str_getcsv($line, $delimiter);
            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = isset($values[$index]) ? trim((string) $values[$index]) : null;
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
            $cells = array_map(static function ($cell) {
                return trim(strip_tags(html_entity_decode($cell)));
            }, $cellMatches[1]);

            if ($trIndex === 0) {
                $headers = array_map(fn($h) => $this->normalizeHeader((string) $h), $cells);
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

    private function validateImportFile(Request $request): UploadedFile
    {
        $request->validate([
            'import_file' => 'required|file|max:5120',
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('import_file');
        $ext = strtolower((string) $file->getClientOriginalExtension());
        $allowed = ['csv', 'txt', 'xls', 'xlsx'];

        if (! in_array($ext, $allowed, true)) {
            abort(422, 'Desteklenmeyen dosya tipi. Sadece csv, txt, xls, xlsx kabul edilir.');
        }

        return $file;
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

        $file = $this->validateImportFile($request);
        return $this->parseImportFile($file);
    }

    private function importSuccessResponse(Request $request, string $message, int $created): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'message' => $message, 'created' => $created]);
        }
        return back()->with('status', $message);
    }

    private function importFailureResponse(Request $request, string $message): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => false, 'message' => $message], 422);
        }
        return back()->withErrors(['import_file' => $message]);
    }

    private function normalizeHeader(string $header): string
    {
        $h = preg_replace('/^\xEF\xBB\xBF/u', '', $header) ?? $header;
        $h = mb_strtolower(trim($h));
        $h = str_replace(['ç', 'ğ', 'ı', 'İ', 'ö', 'ş', 'ü'], ['c', 'g', 'i', 'i', 'o', 's', 'u'], $h);
        $h = str_replace([' ', '-', '/'], '_', $h);
        $h = preg_replace('/_+/', '_', $h) ?? $h;
        $h = trim($h, "_ \t\n\r\0\x0B");

        $map = [
            'ad_soyad' => 'name',
            'ad' => 'name',
            'e_posta' => 'email',
            'eposta' => 'email',
            'telefon' => 'phone',
            'sifre' => 'password',
            'ogrenci_numarasi' => 'student_number',
            'dogum_tarihi' => 'birth_date',
            'sinif_adi' => 'class_name',
            'aktif_mi' => 'is_active',
            'brans' => 'branch',
            'brans_adi' => 'branch',
            'siniflar' => 'class_names',
            'sinif_seviyesi' => 'grade_level',
            'sube' => 'section',
            'aciklama' => 'description',
            'sube_ogretmeni_e_posta' => 'homeroom_teacher_email',
        ];

        return $map[$h] ?? $h;
    }

    private function normalizeRowKeys(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[$this->normalizeHeader((string) $key)] = is_string($value) ? trim($value) : $value;
        }
        return $normalized;
    }

    public function assignRole(Request $request, User $user)
    {
        $data = $request->validate(['role_id' => 'required|exists:roles,id']);
        $user->roles()->sync([$data['role_id']]);
        return back()->with('status', 'Rol atandi.');
    }

    public function assignClass(Request $request, User $user)
    {
        $data = $request->validate(['class_id' => 'required|exists:classes,id']);
        $user->classes()->syncWithoutDetaching([$data['class_id']]);
        return back()->with('status', 'Sinif atamasi yapildi.');
    }

    public function updateStudent(Request $request, Student $student)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($student->user_id)],
            'phone' => 'nullable|string|max:30',
            'student_number' => ['required', 'string', 'max:50', Rule::unique('students', 'student_number')->ignore($student->id)],
            'birth_date' => 'nullable|date',
            'class_id' => 'nullable|exists:classes,id',
            'is_active' => 'nullable|boolean',
        ]);

        $user = User::findOrFail($student->user_id);
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        $student->update([
            'student_number' => $data['student_number'],
            'birth_date' => $data['birth_date'] ?? null,
            'class_id' => $data['class_id'] ?? null,
        ]);

        return redirect()->route('users.index', ['tab' => 'ogrenciler'])->with('status', 'Öğrenci kaydı güncellendi.');
    }

    public function updateTeacher(Request $request, Teacher $teacher)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($teacher->user_id)],
            'phone' => 'nullable|string|max:30',
            'branch' => 'nullable|string|max:255',
            'class_ids' => 'nullable|array',
            'class_ids.*' => 'exists:classes,id',
            'is_active' => 'nullable|boolean',
        ]);

        $user = User::findOrFail($teacher->user_id);
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        $branchName = null;
        if (! empty($data['branch'])) {
            $branch = Schema::hasTable('branches')
                ? Branch::firstOrCreate(['name' => trim((string) $data['branch'])])
                : null;
            $branchName = $branch?->name ?? trim((string) $data['branch']);
        }

        $teacher->update([
            'branch' => $branchName,
        ]);

        $user->classes()->sync($data['class_ids'] ?? []);

        return redirect()->route('users.index', ['tab' => 'ogretmenler'])->with('status', 'Öğretmen kaydı güncellendi.');
    }

    public function destroyStudent(Student $student)
    {
        $student->load('user');
        if ($student->user) {
            $student->user->delete();
        } else {
            $student->delete();
        }
        return back()->with('status', 'Öğrenci kaydı silindi.');
    }

    public function destroyTeacher(Teacher $teacher)
    {
        $teacher->load('user');
        if ($teacher->user) {
            DB::table('class_user')->where('user_id', $teacher->user->id)->delete();
            $teacher->user->delete();
        } else {
            $teacher->delete();
        }
        return back()->with('status', 'Öğretmen kaydı silindi.');
    }

    public function destroyClass(SchoolClass $class)
    {
        DB::table('class_user')->where('class_id', $class->id)->delete();
        Student::where('class_id', $class->id)->update(['class_id' => null]);
        $class->delete();
        return back()->with('status', 'Sınıf kaydı silindi.');
    }

    public function updateClass(Request $request, SchoolClass $class)
    {
        $hasSectionColumn = Schema::hasColumn('classes', 'section');
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'section' => $hasSectionColumn ? 'nullable|string|max:10' : 'nullable',
        ]);

        $payload = ['name' => trim($data['name'])];
        if ($hasSectionColumn) {
            $payload['section'] = isset($data['section']) && $data['section'] !== ''
                ? strtoupper(trim((string) $data['section']))
                : null;
        }

        $class->update($payload);
        return back()->with('status', 'Sınıf bilgisi güncellendi.');
    }
}

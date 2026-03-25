<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\GradeAssignmentSubmissionRequest;
use App\Http\Requests\StoreAssignmentRequest;
use App\Http\Requests\StoreAssignmentSubmissionRequest;
use App\Http\Requests\UpdateAssignmentRequest;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Lesson;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\AssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssignmentController extends Controller
{
    private function scopeAssignmentsForUser($query, User $user)
    {
        if ($user->hasRole('admin')) {
            return $query;
        }

        if ($user->hasRole('teacher')) {
            return $query->where('teacher_id', $user->id);
        }

        if ($user->hasRole('student')) {
            $classIds = $user->classes()->pluck('classes.id');
            return $query->where(function ($sub) use ($user, $classIds) {
                $sub->where('student_id', $user->id)
                    ->orWhereIn('class_id', $classIds)
                    ->orWhere(function ($all) {
                        $all->whereNull('student_id')->whereNull('class_id');
                    });
            });
        }

        return $query->whereRaw('1 = 0');
    }

    public function index()
    {
        $user = request()->user();

        $assignments = $this->scopeAssignmentsForUser(
            Assignment::with(['teacher:id,name', 'student:id,name', 'submissions']),
            $user
        )
            ->latest()
            ->paginate(10);

        return view('assignments.index', compact('assignments'));
    }

    public function create()
    {
        return redirect()->route('assignments.wizard');
    }

    public function wizard()
    {
        $user = request()->user();
        $classes = $user->hasRole('admin')
            ? SchoolClass::orderBy('grade_level')->orderBy('name')->get(['id', 'name', 'grade_level', 'section'])
            : $user->classes()->orderBy('grade_level')->orderBy('name')->get(['classes.id', 'classes.name', 'classes.grade_level', 'classes.section']);

        $students = User::query()
            ->whereHas('roles', fn($q) => $q->where('name', 'student'))
            ->whereHas('classes', fn($q) => $q->whereIn('classes.id', $classes->pluck('id')))
            ->with(['classes:id,name'])
            ->get(['id', 'name']);

        $lessons = $user->hasRole('admin')
            ? Lesson::where('is_active', true)->orderBy('name')->get(['id', 'name'])
            : $user->lessons()->where('is_active', true)->orderBy('name')->get(['lessons.id', 'lessons.name']);

        return view('assignments.wizard', compact('classes', 'students', 'lessons'));
    }

    public function storeWizard(Request $request, AssignmentService $service)
    {
        $user = $request->user();
        abort_unless($user->hasRole(['admin', 'teacher']), 403);

        $data = $request->validate([
            'period' => 'required|string|max:50',
            'assign_scope' => 'required|in:student,class',
            'assignment_type' => 'required|string|max:50',
            'student_type' => 'required|string|max:50',
            'class_id' => 'nullable|exists:classes,id',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:users,id',
            'lesson_id' => 'required|exists:lessons,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_at' => 'required|date',
            'due_at' => 'required|date|after_or_equal:start_at',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,zip,png,jpg,jpeg|max:20480',
        ]);

        $created = 0;
        DB::transaction(function () use ($data, $service, $user, &$created) {
            $basePayload = [
                'teacher_id' => $user->id,
                'lesson_id' => (int) $data['lesson_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'start_at' => $data['start_at'],
                'due_at' => $data['due_at'],
                'period' => $data['period'],
                'assign_scope' => $data['assign_scope'],
                'assignment_type' => $data['assignment_type'],
                'student_type' => $data['student_type'],
            ];

            if ($data['assign_scope'] === 'class') {
                $service->create($basePayload + [
                    'class_id' => $data['class_id'] ?? null,
                    'student_id' => null,
                    'attachment' => $data['attachment'] ?? null,
                ]);
                $created = 1;
                return;
            }

            $studentIds = array_values(array_unique($data['student_ids'] ?? []));
            foreach ($studentIds as $idx => $studentId) {
                $payload = $basePayload + [
                    'class_id' => $data['class_id'] ?? null,
                    'student_id' => (int) $studentId,
                ];
                if ($idx === 0 && !empty($data['attachment'])) {
                    $payload['attachment'] = $data['attachment'];
                }
                $service->create($payload);
                $created++;
            }
        });

        if ($created === 0) {
            return back()->withErrors(['student_ids' => 'Ogrenci bazli secimde en az bir ogrenci secmelisiniz.']);
        }

        return redirect()->route('assignments.index')->with('status', "Odev olusturuldu. Kayit: {$created}");
    }

    public function store(StoreAssignmentRequest $request, AssignmentService $service)
    {
        $service->create($request->validated() + ['teacher_id' => $request->user()->id]);
        return redirect()->route('assignments.index')->with('status', 'Odev olusturuldu.');
    }

    public function show(Assignment $assignment)
    {
        $user = request()->user();

        $canAccess = $this->scopeAssignmentsForUser(Assignment::query()->whereKey($assignment->id), $user)->exists();
        abort_unless($canAccess, 403);

        $assignment->load(['submissions.student:id,name']);
        return view('assignments.show', compact('assignment'));
    }

    public function edit(Assignment $assignment)
    {
        $user = request()->user();
        if ($user->hasRole('teacher') && $assignment->teacher_id !== $user->id) {
            abort(403);
        }

        $classes = SchoolClass::all();
        $students = User::whereHas('roles', fn ($q) => $q->where('name', 'student'))->get(['id', 'name']);
        return view('assignments.edit', compact('assignment', 'classes', 'students'));
    }

    public function update(UpdateAssignmentRequest $request, Assignment $assignment, AssignmentService $service)
    {
        if ($request->user()->hasRole('teacher') && $assignment->teacher_id !== $request->user()->id) {
            abort(403);
        }

        $service->update($assignment, $request->validated());
        return redirect()->route('assignments.index')->with('status', 'Odev guncellendi.');
    }

    public function destroy(Assignment $assignment)
    {
        $user = request()->user();
        if ($user->hasRole('teacher') && $assignment->teacher_id !== $user->id) {
            abort(403);
        }

        $assignment->delete();
        return redirect()->route('assignments.index')->with('status', 'Odev silindi.');
    }

    public function submit(StoreAssignmentSubmissionRequest $request, Assignment $assignment, AssignmentService $service)
    {
        $canAccess = $this->scopeAssignmentsForUser(Assignment::query()->whereKey($assignment->id), $request->user())->exists();
        abort_unless($canAccess, 403);

        $service->submit($assignment, $request->user()->id, $request->validated());
        return back()->with('status', 'Odev teslim edildi.');
    }

    public function grade(GradeAssignmentSubmissionRequest $request, AssignmentSubmission $submission, AssignmentService $service)
    {
        $user = $request->user();
        if ($user->hasRole('teacher') && $submission->assignment?->teacher_id !== $user->id) {
            abort(403);
        }

        $service->grade($submission, $request->validated());
        return back()->with('status', 'Odev puanlandi.');
    }
}

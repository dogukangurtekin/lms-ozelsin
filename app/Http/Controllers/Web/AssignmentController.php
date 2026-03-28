<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\GradeAssignmentSubmissionRequest;
use App\Http\Requests\StoreAssignmentRequest;
use App\Http\Requests\StoreAssignmentSubmissionRequest;
use App\Http\Requests\UpdateAssignmentRequest;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Book;
use App\Models\Lesson;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\AssignmentService;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

        $books = Book::query()
            ->with(['tests:id,book_id,unit_name,test_name'])
            ->orderBy('title')
            ->get(['id', 'title', 'lesson']);

        $booksForJs = $books->map(function ($book) {
            return [
                'id' => $book->id,
                'title' => $book->title,
                'lesson' => $book->lesson,
                'tests' => $book->tests->map(function ($t) {
                    return [
                        'id' => $t->id,
                        'unit_name' => $t->unit_name,
                        'test_name' => $t->test_name,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        $lessonsForJs = $lessons->map(function ($l) {
            return ['id' => $l->id, 'name' => $l->name];
        })->values()->all();

        return view('assignments.wizard', compact('classes', 'students', 'lessons', 'books', 'booksForJs', 'lessonsForJs'));
    }

    public function storeWizard(Request $request, AssignmentService $service, PushNotificationService $pushNotifications)
    {
        $user = $request->user();
        abort_unless($user->hasRole(['admin', 'teacher']), 403);
        if ($request->input('class_id') === 'all') {
            $request->merge(['class_id' => null]);
        }

        $data = $request->validate([
            'period' => 'required|string|max:50',
            'assign_scope' => 'required|in:student,class',
            'assignment_type' => 'required|string|max:50',
            'student_type' => 'required|string|max:50',
            'class_id' => 'nullable|exists:classes,id',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:users,id',
            'lesson_id' => 'required|exists:lessons,id',
            'book_id' => 'nullable|exists:books,id',
            'book_test_id' => 'nullable|exists:book_tests,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_at' => 'required|date',
            'due_at' => 'required|date|after_or_equal:start_at',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,zip,png,jpg,jpeg|max:20480',
        ]);

        $created = 0;
        $createdAssignments = collect();
        DB::transaction(function () use ($data, $service, $user, &$created, &$createdAssignments) {
            $bookLabel = null;
            if (!empty($data['book_id'])) {
                $book = Book::query()->find((int) $data['book_id']);
                if ($book) {
                    $bookLabel = 'Kitap: '.$book->title;
                }
            }

            if (!empty($data['book_test_id'])) {
                $test = \App\Models\BookTest::query()->find((int) $data['book_test_id']);
                if ($test) {
                    $testText = trim(($test->unit_name ? $test->unit_name.' - ' : '').$test->test_name);
                    $bookLabel = trim(($bookLabel ? $bookLabel.' | ' : '').'Test: '.$testText);
                }
            }

            $descriptionWithBook = $data['description'] ?? null;
            if ($bookLabel) {
                $descriptionWithBook = trim(($descriptionWithBook ? $descriptionWithBook."\n\n" : '').$bookLabel);
            }

            $basePayload = [
                'teacher_id' => $user->id,
                'lesson_id' => (int) $data['lesson_id'],
                'title' => $data['title'],
                'description' => $descriptionWithBook,
                'start_at' => $data['start_at'],
                'due_at' => $data['due_at'],
                'period' => $data['period'],
                'assign_scope' => $data['assign_scope'],
                'assignment_type' => $data['assignment_type'],
                'student_type' => $data['student_type'],
            ];

            if ($data['assign_scope'] === 'class') {
                $createdAssignments->push($service->create($basePayload + [
                    'class_id' => $data['class_id'] ?? null,
                    'student_id' => null,
                    'attachment' => $data['attachment'] ?? null,
                ]));
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
                $createdAssignments->push($service->create($payload));
                $created++;
            }
        });

        if ($created === 0) {
            return back()->withErrors(['student_ids' => 'Ogrenci bazli secimde en az bir ogrenci secmelisiniz.']);
        }

        $createdAssignments->each(function (Assignment $assignment) use ($pushNotifications) {
            $this->notifyAssignmentCreated($assignment, $pushNotifications);
        });

        return redirect()->route('assignments.index')->with('status', "Odev olusturuldu. Kayit: {$created}");
    }

    public function store(StoreAssignmentRequest $request, AssignmentService $service, PushNotificationService $pushNotifications)
    {
        $assignment = $service->create($request->validated() + ['teacher_id' => $request->user()->id]);
        $this->notifyAssignmentCreated($assignment, $pushNotifications);
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

    private function notifyAssignmentCreated(Assignment $assignment, PushNotificationService $pushNotifications): void
    {
        $recipientUserIds = $this->assignmentRecipientUserIds($assignment);
        if ($recipientUserIds->isEmpty()) {
            return;
        }

        $assignment->loadMissing(['class:id,name', 'student:id,name']);
        $targetLabel = $assignment->class?->name ?? $assignment->student?->name ?? 'ogrenci';

        $pushNotifications->sendToUsers(
            $recipientUserIds,
            'Yeni odev yayimlandi',
            "Sayin kullanici, {$targetLabel} icin {$assignment->title} baslikli yeni odev yayimlandi.",
            route('assignments.show', $assignment)
        );
    }

    private function assignmentRecipientUserIds(Assignment $assignment): Collection
    {
        $studentIds = collect();

        if ($assignment->student_id) {
            $studentIds->push((int) $assignment->student_id);
        } elseif ($assignment->class_id) {
            $studentIds = Student::query()
                ->where('class_id', $assignment->class_id)
                ->pluck('user_id');
        }

        if ($studentIds->isEmpty()) {
            return collect();
        }

        $studentProfileIds = Student::query()
            ->whereIn('user_id', $studentIds)
            ->pluck('id');

        $parentUserIds = DB::table('parent_student')
            ->join('parents', 'parents.id', '=', 'parent_student.parent_id')
            ->whereIn('parent_student.student_id', $studentProfileIds)
            ->pluck('parents.user_id');

        return $studentIds
            ->merge($parentUserIds)
            ->filter()
            ->unique()
            ->values();
    }
}

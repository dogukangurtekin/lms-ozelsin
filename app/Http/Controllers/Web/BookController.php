<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\BookTest;
use App\Models\Lesson;
use App\Models\SchoolClass;
use App\Services\BookService;
use Throwable;

class BookController extends Controller
{
    private function normalizeAnswerKey(?string $answerKey): ?string
    {
        $raw = trim((string) $answerKey);
        if ($raw === '') {
            return null;
        }

        // Accept old formats like "1:A,2:B" and compact new formats like "ABCDDABD".
        preg_match_all('/[A-E]/i', $raw, $matches);
        $letters = $matches[0] ?? [];
        if (empty($letters)) {
            return null;
        }

        return strtoupper(implode('', $letters));
    }

    public function index()
    {
        $lessons = Lesson::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name');

        $classes = SchoolClass::query()
            ->orderBy('grade_level')
            ->orderBy('section')
            ->get(['id', 'name', 'grade_level']);

        $selectedClass = null;
        if (request()->filled('class_id')) {
            $selectedClass = $classes->firstWhere('id', (int) request('class_id'));
        }

        $books = Book::query()
            ->with('creator:id,name')
            ->when($selectedClass, fn($q) => $q->where('grade_level', $selectedClass->grade_level))
            ->when(request('grade_level') && ! $selectedClass, fn($q, $v) => $q->where('grade_level', $v))
            ->when(request('lesson'), fn($q, $v) => $q->where('lesson', $v))
            ->when(request('title'), fn($q, $v) => $q->where('title', 'like', '%'.$v.'%'))
            ->when(request('publisher'), function ($q, $v) {
                $q->where(function ($inner) use ($v) {
                    $inner->where('title', 'like', '%'.$v.'%')
                        ->orWhere('description', 'like', '%'.$v.'%');
                });
            })
            ->latest()
            ->paginate(10);

        return view('books.index', compact('books', 'lessons', 'classes'));
    }

    public function show(Book $book)
    {
        $book->load('creator:id,name');
        return view('books.show', compact('book'));
    }

    public function create()
    {
        $gradeLevels = SchoolClass::query()
            ->select('grade_level')
            ->distinct()
            ->orderBy('grade_level')
            ->pluck('grade_level');

        $lessons = Lesson::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('books.create', compact('gradeLevels', 'lessons'));
    }

    public function store(StoreBookRequest $request, BookService $service)
    {
        try {
            $service->create($request->validated() + ['created_by' => $request->user()->id]);

            return redirect()->route('books.index')->with('status', 'Kitap basariyla kaydedildi.');
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->withErrors([
                'book' => 'Kitap kaydedilemedi. Lutfen alanlari kontrol edip tekrar deneyin.',
            ]);
        }
    }

    public function edit(Book $book)
    {
        $gradeLevels = SchoolClass::query()
            ->select('grade_level')
            ->distinct()
            ->orderBy('grade_level')
            ->pluck('grade_level');

        $lessons = Lesson::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('books.edit', compact('book', 'gradeLevels', 'lessons'));
    }

    public function update(UpdateBookRequest $request, Book $book, BookService $service)
    {
        try {
            $service->update($book, $request->validated());

            return redirect()->route('books.index')->with('status', 'Kitap basariyla guncellendi.');
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->withErrors([
                'book' => 'Kitap guncellenemedi. Lutfen tekrar deneyin.',
            ]);
        }
    }

    public function destroy(Book $book)
    {
        $book->delete();

        return redirect()->route('books.index')->with('status', 'Kitap silindi.');
    }

    public function details(Book $book)
    {
        $book->load(['creator:id,name', 'tests']);

        return view('books.details', compact('book'));
    }

    public function storeDetail(\Illuminate\Http\Request $request, Book $book)
    {
        $data = $request->validate([
            'unit_name' => 'required|string|max:255',
            'test_name' => 'required|string|max:255',
            'question_count' => 'required|integer|min:0|max:500',
            'answer_key' => 'nullable|string|max:10000',
            'sort_order' => 'nullable|integer|min:0|max:5000',
        ]);

        $book->tests()->create([
            'unit_name' => $data['unit_name'],
            'test_name' => $data['test_name'],
            'question_count' => (int) $data['question_count'],
            'answer_key' => $this->normalizeAnswerKey($data['answer_key'] ?? null),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return back()->with('status', 'Kitap detayi eklendi.');
    }

    public function updateDetail(\Illuminate\Http\Request $request, Book $book, BookTest $detail)
    {
        abort_unless($detail->book_id === $book->id, 404);

        $data = $request->validate([
            'unit_name' => 'required|string|max:255',
            'test_name' => 'required|string|max:255',
            'question_count' => 'required|integer|min:0|max:500',
            'answer_key' => 'nullable|string|max:10000',
            'sort_order' => 'nullable|integer|min:0|max:5000',
        ]);

        $detail->update([
            'unit_name' => $data['unit_name'],
            'test_name' => $data['test_name'],
            'question_count' => (int) $data['question_count'],
            'answer_key' => $this->normalizeAnswerKey($data['answer_key'] ?? null),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return back()->with('status', 'Kitap detayi guncellendi.');
    }

    public function destroyDetail(Book $book, BookTest $detail)
    {
        abort_unless($detail->book_id === $book->id, 404);
        $detail->delete();

        return back()->with('status', 'Kitap detayi silindi.');
    }
}

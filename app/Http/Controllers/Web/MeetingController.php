<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMeetingRequest;
use App\Http\Requests\UpdateMeetingRequest;
use App\Models\Meeting;
use App\Models\User;
use App\Services\MeetingService;

class MeetingController extends Controller
{
    public function index()
    {
        $user = request()->user();

        $query = Meeting::with(['teacher:id,name', 'student:id,name', 'parentUser:id,name']);

        if (! $user->hasRole('admin')) {
            if ($user->hasRole('teacher')) {
                $query->where('teacher_id', $user->id);
            } elseif ($user->hasRole('student')) {
                $query->where('student_id', $user->id);
            } elseif ($user->hasRole('parent')) {
                $query->where('parent_id', $user->id);
            }
        }

        $meetings = $query->latest('meeting_at')->paginate(10);

        return view('meetings.index', compact('meetings'));
    }

    public function show(Meeting $meeting)
    {
        $user = request()->user();

        $canAccess = $user->hasRole('admin')
            || $meeting->teacher_id === $user->id
            || $meeting->student_id === $user->id
            || $meeting->parent_id === $user->id;

        abort_unless($canAccess, 403);

        $meeting->load(['teacher:id,name', 'student:id,name', 'parentUser:id,name']);

        return view('meetings.show', compact('meeting'));
    }

    public function create()
    {
        $students = User::whereHas('roles', fn($q) => $q->where('name', 'student'))->get(['id', 'name']);
        $parents = User::whereHas('roles', fn($q) => $q->where('name', 'parent'))->get(['id', 'name']);
        return view('meetings.create', compact('students', 'parents'));
    }

    public function store(StoreMeetingRequest $request, MeetingService $service)
    {
        $service->create($request->validated() + [
            'teacher_id' => $request->user()->id,
            'status' => $request->input('status', 'scheduled'),
        ]);

        return redirect()->route('meetings.index')->with('status', 'Gorusme olusturuldu.');
    }

    public function edit(Meeting $meeting)
    {
        abort_unless(request()->user()->hasRole(['admin', 'teacher']), 403);

        $students = User::whereHas('roles', fn($q) => $q->where('name', 'student'))->get(['id', 'name']);
        $parents = User::whereHas('roles', fn($q) => $q->where('name', 'parent'))->get(['id', 'name']);
        return view('meetings.edit', compact('meeting', 'students', 'parents'));
    }

    public function update(UpdateMeetingRequest $request, Meeting $meeting, MeetingService $service)
    {
        if ($request->user()->hasRole('teacher') && $meeting->teacher_id !== $request->user()->id) {
            abort(403);
        }

        $service->update($meeting, $request->validated());
        return redirect()->route('meetings.index')->with('status', 'Gorusme guncellendi.');
    }

    public function destroy(Meeting $meeting)
    {
        $user = request()->user();
        if ($user->hasRole('teacher') && $meeting->teacher_id !== $user->id) {
            abort(403);
        }

        $meeting->delete();
        return back()->with('status', 'Gorusme silindi.');
    }
}

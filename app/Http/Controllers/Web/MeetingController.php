<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMeetingRequest;
use App\Http\Requests\UpdateMeetingRequest;
use App\Models\Meeting;
use App\Models\User;
use App\Services\MeetingService;
use App\Services\PushNotificationService;

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
        $students = User::whereHas('roles', fn ($q) => $q->where('name', 'student'))->get(['id', 'name']);
        $parents = User::whereHas('roles', fn ($q) => $q->where('name', 'parent'))->get(['id', 'name']);

        return view('meetings.create', compact('students', 'parents'));
    }

    public function store(StoreMeetingRequest $request, MeetingService $service, PushNotificationService $pushNotifications)
    {
        $meeting = $service->create($request->validated() + [
            'teacher_id' => $request->user()->id,
            'status' => $request->input('status', 'scheduled'),
        ]);

        $this->notifyMeetingCreated($meeting, $pushNotifications);

        return redirect()->route('meetings.index')->with('status', 'Gorusme olusturuldu.');
    }

    public function edit(Meeting $meeting)
    {
        abort_unless(request()->user()->hasRole(['admin', 'teacher']), 403);

        $students = User::whereHas('roles', fn ($q) => $q->where('name', 'student'))->get(['id', 'name']);
        $parents = User::whereHas('roles', fn ($q) => $q->where('name', 'parent'))->get(['id', 'name']);

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

    public function updateStatus(\Illuminate\Http\Request $request, Meeting $meeting)
    {
        $user = $request->user();

        if ($user->hasRole('teacher') && $meeting->teacher_id !== $user->id) {
            abort(403);
        }

        abort_unless($user->hasRole(['admin', 'teacher']), 403);

        $data = $request->validate([
            'status' => ['required', 'in:completed,cancelled'],
        ]);

        $meeting->update([
            'status' => $data['status'],
        ]);

        return back()->with('status', 'Gorusme durumu guncellendi.');
    }

    public function updateTeacherNote(\Illuminate\Http\Request $request, Meeting $meeting)
    {
        $user = $request->user();
        if ($user->hasRole('teacher') && $meeting->teacher_id !== $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        $meeting->update([
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('meetings.show', $meeting)
            ->with('status', 'Ogretmen notu guncellendi.');
    }

    private function notifyMeetingCreated(Meeting $meeting, PushNotificationService $pushNotifications): void
    {
        $meeting->loadMissing(['student:id,name', 'parentUser:id,name']);

        $recipientUserIds = collect([$meeting->student_id, $meeting->parent_id])
            ->filter()
            ->unique()
            ->values();

        if ($recipientUserIds->isEmpty()) {
            return;
        }

        $studentName = $meeting->student?->name ?? 'ogrenci';
        $meetingTime = optional($meeting->meeting_at)->format('d.m.Y H:i') ?: 'planlanan zaman';

        $pushNotifications->sendToUsers(
            $recipientUserIds,
            'Yeni gorusme bilgilendirmesi',
            "{$studentName} icin {$meetingTime} tarihli yeni bir gorusme planlandi.",
            route('meetings.show', $meeting)
        );
    }
}

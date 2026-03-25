<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GradeAssignmentSubmissionRequest;
use App\Http\Requests\StoreAssignmentRequest;
use App\Http\Requests\StoreAssignmentSubmissionRequest;
use App\Http\Requests\UpdateAssignmentRequest;
use App\Http\Resources\AssignmentResource;
use App\Http\Resources\AssignmentSubmissionResource;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Services\AssignmentService;

class AssignmentController extends Controller
{
    public function index()
    {
        $user = request()->user();

        $query = Assignment::query()->with(['teacher:id,name', 'student:id,name'])
            ->when($user->hasRole('student'), fn ($q) => $q->where(function ($sub) use ($user) {
                $sub->where('student_id', $user->id)->orWhereNull('student_id');
            }));

        return AssignmentResource::collection($query->latest()->paginate(10));
    }

    public function store(StoreAssignmentRequest $request, AssignmentService $service)
    {
        $assignment = $service->create($request->validated() + ['teacher_id' => $request->user()->id]);
        return new AssignmentResource($assignment);
    }

    public function show(Assignment $assignment)
    {
        $assignment->load(['submissions.student:id,name']);
        return new AssignmentResource($assignment);
    }

    public function update(UpdateAssignmentRequest $request, Assignment $assignment, AssignmentService $service)
    {
        return new AssignmentResource($service->update($assignment, $request->validated()));
    }

    public function destroy(Assignment $assignment)
    {
        $assignment->delete();
        return response()->json([], 204);
    }

    public function submit(StoreAssignmentSubmissionRequest $request, Assignment $assignment, AssignmentService $service)
    {
        $submission = $service->submit($assignment, $request->user()->id, $request->validated());
        return new AssignmentSubmissionResource($submission);
    }

    public function grade(GradeAssignmentSubmissionRequest $request, AssignmentSubmission $submission, AssignmentService $service)
    {
        return new AssignmentSubmissionResource($service->grade($submission, $request->validated()));
    }
}

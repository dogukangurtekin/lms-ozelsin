<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMeetingRequest;
use App\Http\Requests\UpdateMeetingRequest;
use App\Http\Resources\MeetingResource;
use App\Models\Meeting;
use App\Services\MeetingService;

class MeetingController extends Controller
{
    public function index()
    {
        return MeetingResource::collection(Meeting::latest('meeting_at')->paginate(10));
    }

    public function store(StoreMeetingRequest $request, MeetingService $service)
    {
        $this->authorize('create', Meeting::class);
        $meeting = $service->create($request->validated() + ['teacher_id' => $request->user()->id]);
        return new MeetingResource($meeting);
    }

    public function show(Meeting $meeting)
    {
        return new MeetingResource($meeting);
    }

    public function update(UpdateMeetingRequest $request, Meeting $meeting, MeetingService $service)
    {
        $this->authorize('update', $meeting);
        return new MeetingResource($service->update($meeting, $request->validated()));
    }

    public function destroy(Meeting $meeting)
    {
        $this->authorize('delete', $meeting);
        $meeting->delete();
        return response()->json([], 204);
    }
}

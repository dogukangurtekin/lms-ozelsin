<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'due_at' => $this->due_at,
            'attachment_url' => $this->attachment ? Storage::url($this->attachment) : null,
            'teacher' => $this->whenLoaded('teacher', fn() => ['id' => $this->teacher->id, 'name' => $this->teacher->name]),
            'student' => $this->whenLoaded('student', fn() => ['id' => $this->student->id, 'name' => $this->student->name]),
            'submissions' => AssignmentSubmissionResource::collection($this->whenLoaded('submissions')),
            'created_at' => $this->created_at,
        ];
    }
}

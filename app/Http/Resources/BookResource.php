<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'cover_image_url' => $this->cover_image ? Storage::url($this->cover_image) : null,
            'content_file_url' => $this->content_file ? Storage::url($this->content_file) : null,
            'grade_level' => $this->grade_level,
            'lesson' => $this->lesson,
            'created_at' => $this->created_at,
        ];
    }
}

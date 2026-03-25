<?php

namespace App\Services;

use App\Models\Meeting;

class MeetingService
{
    public function create(array $data): Meeting
    {
        return Meeting::create($data);
    }

    public function update(Meeting $meeting, array $data): Meeting
    {
        $meeting->update($data);

        return $meeting->refresh();
    }
}

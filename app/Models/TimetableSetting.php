<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimetableSetting extends Model
{
    protected $fillable = [
        'day_start_time',
        'lesson_duration',
        'short_break_duration',
        'lunch_after_period',
        'lunch_duration',
    ];
}

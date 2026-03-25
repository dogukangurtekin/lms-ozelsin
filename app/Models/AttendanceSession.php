<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSession extends Model
{
    protected $fillable = [
        'schedule_id',
        'teacher_id',
        'class_id',
        'lesson_name',
        'attendance_date',
        'taken_at',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'taken_at' => 'datetime',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(TeacherSchedule::class, 'schedule_id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'session_id');
    }
}

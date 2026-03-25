<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'lesson_teacher', 'lesson_id', 'teacher_id')->withTimestamps();
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(TeacherSchedule::class, 'lesson_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    protected $fillable = ['created_by', 'title', 'description', 'cover_image', 'content_file', 'grade_level', 'lesson'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tests(): HasMany
    {
        return $this->hasMany(BookTest::class)->orderBy('sort_order')->orderBy('id');
    }
}

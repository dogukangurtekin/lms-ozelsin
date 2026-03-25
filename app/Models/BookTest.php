<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookTest extends Model
{
    protected $fillable = [
        'book_id',
        'unit_name',
        'test_name',
        'question_count',
        'answer_key',
        'sort_order',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}

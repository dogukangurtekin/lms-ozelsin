<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['sender_id', 'receiver_id', 'type', 'channel', 'content', 'sent_at'];
    protected $casts = ['sent_at' => 'datetime'];
}

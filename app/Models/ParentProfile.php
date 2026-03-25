<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParentProfile extends Model
{
    protected $table = 'parents';
    protected $fillable = ['user_id', 'relation_type'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = ['name', 'label'];

    public function modulePermissions(): HasMany
    {
        return $this->hasMany(RoleModulePermission::class);
    }
}

<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withTimestamps();
    }

    public function hasRole(string|array $role): bool
    {
        $roles = is_array($role) ? $role : [$role];
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    public function primaryRoleName(): ?string
    {
        return $this->roles()->value('name');
    }

    public function canAccessModule(string $moduleKey): bool
    {
        $roleIds = $this->roles()->pluck('roles.id');
        if ($roleIds->isEmpty()) {
            return false;
        }

        $allowed = RoleModulePermission::query()
            ->whereIn('role_id', $roleIds)
            ->where('module_key', $moduleKey)
            ->where('can_access', true)
            ->exists();

        if ($allowed) {
            return true;
        }

        return ! RoleModulePermission::query()
            ->whereIn('role_id', $roleIds)
            ->where('module_key', $moduleKey)
            ->exists();
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class, 'created_by');
    }

    public function assignmentsAsTeacher(): HasMany
    {
        return $this->hasMany(Assignment::class, 'teacher_id');
    }

    public function assignmentsAsStudent(): HasMany
    {
        return $this->hasMany(Assignment::class, 'student_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class, 'student_id');
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_user', 'user_id', 'class_id')->withTimestamps();
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'lesson_teacher', 'teacher_id', 'lesson_id')->withTimestamps();
    }
}

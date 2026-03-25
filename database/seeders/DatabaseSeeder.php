<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $roles = [
            'admin' => 'Admin',
            'teacher' => 'Ogretmen',
            'student' => 'Ogrenci',
            'parent' => 'Veli',
        ];

        foreach ($roles as $name => $label) {
            Role::query()->firstOrCreate(['name' => $name], ['label' => $label]);
        }

        $users = [
            ['name' => 'System Admin', 'email' => 'admin@lms.local', 'phone' => '905551111111', 'role' => 'admin'],
            ['name' => 'Teacher One', 'email' => 'teacher@lms.local', 'phone' => '905552222222', 'role' => 'teacher'],
            ['name' => 'Student One', 'email' => 'student@lms.local', 'phone' => '905553333333', 'role' => 'student'],
            ['name' => 'Parent One', 'email' => 'parent@lms.local', 'phone' => '905554444444', 'role' => 'parent'],
        ];

        foreach ($users as $item) {
            $user = User::query()->firstOrCreate(['email' => $item['email']], [
                'name' => $item['name'],
                'phone' => $item['phone'],
                'password' => Hash::make('Password123!'),
                'is_active' => true,
            ]);

            $roleId = Role::query()->where('name', $item['role'])->value('id');
            if ($roleId) {
                $user->roles()->syncWithoutDetaching([$roleId]);
            }
        }

        SchoolClass::query()->firstOrCreate(
            ['name' => '6-A'],
            ['grade_level' => '6', 'description' => 'Ornek sinif']
        );
    }
}

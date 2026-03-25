<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('role_module_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->string('module_key');
            $table->boolean('can_access')->default(true);
            $table->timestamps();
            $table->unique(['role_id', 'module_key']);
        });

        $modules = array_keys(config('lms_modules', []));
        $roleIds = DB::table('roles')->pluck('id')->all();
        $now = now();

        foreach ($roleIds as $roleId) {
            foreach ($modules as $moduleKey) {
                DB::table('role_module_permissions')->insert([
                    'role_id' => $roleId,
                    'module_key' => $moduleKey,
                    'can_access' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_module_permissions');
    }
};

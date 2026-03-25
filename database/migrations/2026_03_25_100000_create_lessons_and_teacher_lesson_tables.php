<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->nullable()->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('lesson_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['lesson_id', 'teacher_id']);
        });

        Schema::table('teacher_schedules', function (Blueprint $table) {
            $table->foreignId('lesson_id')->nullable()->after('class_id')->constrained('lessons')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('teacher_schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lesson_id');
        });

        Schema::dropIfExists('lesson_teacher');
        Schema::dropIfExists('lessons');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->foreignId('lesson_id')->nullable()->after('student_id')->constrained('lessons')->nullOnDelete();
            $table->timestamp('start_at')->nullable()->after('description');
            $table->string('period')->nullable()->after('start_at');
            $table->string('assign_scope')->nullable()->after('period'); // student|class
            $table->string('assignment_type')->nullable()->after('assign_scope');
            $table->string('student_type')->nullable()->after('assignment_type');
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lesson_id');
            $table->dropColumn(['start_at', 'period', 'assign_scope', 'assignment_type', 'student_type']);
        });
    }
};

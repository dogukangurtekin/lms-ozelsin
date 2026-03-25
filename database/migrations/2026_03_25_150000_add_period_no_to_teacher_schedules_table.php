<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('teacher_schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('teacher_schedules', 'period_no')) {
                $table->unsignedTinyInteger('period_no')->nullable()->after('day_of_week');
                $table->index(['class_id', 'day_of_week', 'period_no'], 'idx_schedule_class_day_period');
            }
        });
    }

    public function down(): void
    {
        Schema::table('teacher_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('teacher_schedules', 'period_no')) {
                $table->dropIndex('idx_schedule_class_day_period');
                $table->dropColumn('period_no');
            }
        });
    }
};

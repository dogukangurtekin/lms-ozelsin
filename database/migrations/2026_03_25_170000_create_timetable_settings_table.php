<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('timetable_settings')) {
            return;
        }

        Schema::create('timetable_settings', function (Blueprint $table) {
            $table->id();
            $table->time('day_start_time')->default('08:50:00');
            $table->unsignedSmallInteger('lesson_duration')->default(35);
            $table->unsignedSmallInteger('short_break_duration')->default(10);
            $table->unsignedTinyInteger('lunch_after_period')->nullable();
            $table->unsignedSmallInteger('lunch_duration')->default(40);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('timetable_settings')) {
            return;
        }

        Schema::dropIfExists('timetable_settings');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_logs', 'scheduled_for')) {
                $table->timestamp('scheduled_for')->nullable()->after('sender_phone');
                $table->index('scheduled_for');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_logs', 'scheduled_for')) {
                $table->dropIndex(['scheduled_for']);
                $table->dropColumn('scheduled_for');
            }
        });
    }
};


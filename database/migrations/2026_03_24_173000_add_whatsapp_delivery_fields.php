<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('channel')->default('whatsapp')->after('type');
        });

        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->string('receiver_phone', 30)->nullable()->after('provider_message_id');
            $table->text('error_message')->nullable()->after('response_payload');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->dropColumn(['receiver_phone', 'error_message']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('channel');
        });
    }
};

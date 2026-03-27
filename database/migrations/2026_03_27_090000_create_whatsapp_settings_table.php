<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_settings')) {
            Schema::create('whatsapp_settings', function (Blueprint $table) {
                $table->id();
                $table->string('sender_phone', 30)->nullable();
                $table->timestamps();
            });
        }

        Schema::table('whatsapp_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_logs', 'sender_phone')) {
                $table->string('sender_phone', 30)->nullable()->after('receiver_phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_logs', 'sender_phone')) {
                $table->dropColumn('sender_phone');
            }
        });

        Schema::dropIfExists('whatsapp_settings');
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_device_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_key', 120);
            $table->string('device_label', 160)->nullable();
            $table->string('platform', 40)->nullable();
            $table->string('browser', 40)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('permission_state', 20)->default('default');
            $table->text('subscription_endpoint')->nullable();
            $table->boolean('is_standalone')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'device_key']);
            $table->index(['user_id', 'permission_state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_device_statuses');
    }
};

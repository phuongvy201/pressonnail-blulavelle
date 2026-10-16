<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_push_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('guest_device_key', 64)->nullable()->index();
            $table->string('customer_email')->nullable()->index();
            $table->string('expo_push_token', 191)->unique();
            $table->string('platform', 16)->nullable();
            $table->string('app_version', 32)->nullable();
            $table->timestamp('last_registered_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'expo_push_token']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_push_tokens');
    }
};

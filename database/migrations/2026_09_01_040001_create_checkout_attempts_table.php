<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('idempotency_key', 128)->unique();
            $table->string('request_hash', 64);
            $table->string('status', 32)->default('created');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_token', 64)->nullable();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('payment_intent_id')->nullable();
            $table->json('request_snapshot')->nullable();
            $table->json('response_body')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('payment_intent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_attempts');
    }
};

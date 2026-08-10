<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virtual_nail_trials', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 120)->nullable()->index();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('nail_shape', 80);
            $table->string('nail_length', 80);
            $table->string('hand_image_path');
            $table->string('result_image_path')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->string('provider', 40)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_nail_trials');
    }
};

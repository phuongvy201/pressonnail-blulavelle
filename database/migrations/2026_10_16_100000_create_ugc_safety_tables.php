<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ugc_reports', function (Blueprint $table) {
            $table->id();
            $table->string('target_type', 32);
            $table->unsignedBigInteger('target_id');
            $table->string('reason', 64);
            $table->text('details')->nullable();
            $table->foreignId('reporter_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reporter_guest_token', 64)->nullable();
            $table->string('status', 32)->default('open');
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
            $table->index('status');
        });

        Schema::create('ugc_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 32);
            $table->unsignedBigInteger('target_id');
            $table->string('blocker_key', 80);
            $table->foreignId('blocker_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('blocker_guest_token', 64)->nullable();
            $table->timestamps();

            $table->unique(['scope', 'target_id', 'blocker_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ugc_blocks');
        Schema::dropIfExists('ugc_reports');
    }
};

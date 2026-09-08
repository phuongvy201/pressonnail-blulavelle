<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_upload_assets', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 64)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_token', 64)->nullable()->index();
            $table->string('purpose', 64)->index();
            $table->string('mime_type', 64);
            $table->unsignedBigInteger('expected_size');
            $table->string('checksum_sha256', 64);
            $table->string('disk', 32)->default('local');
            $table->string('path')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('byte_size')->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->string('upload_token', 64)->nullable();
            $table->timestamp('upload_expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('deleted_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'purpose', 'created_at']);
            $table->index(['guest_token', 'purpose', 'created_at']);
        });

        Schema::create('nail_try_ons', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 64)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_token', 64)->nullable()->index();
            $table->foreignId('hand_asset_id')->constrained('api_upload_assets')->cascadeOnDelete();
            $table->foreignId('output_asset_id')->nullable()->constrained('api_upload_assets')->nullOnDelete();
            $table->foreignId('parent_try_on_id')->nullable()->constrained('nail_try_ons')->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('variant_id')->nullable()->index();
            $table->string('try_on_asset_version', 120)->nullable();
            $table->string('hand_side', 16)->default('auto');
            $table->string('nail_shape', 80)->nullable();
            $table->string('nail_length', 80)->nullable();
            $table->string('status', 40)->default('queued')->index();
            $table->string('error_code', 64)->nullable();
            $table->string('error_message', 500)->nullable();
            $table->string('provider', 40)->nullable();
            $table->string('idempotency_key', 128)->nullable();
            $table->string('request_hash', 64)->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('deleted_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['idempotency_key']);
            $table->index(['user_id', 'created_at']);
            $table->index(['guest_token', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nail_try_ons');
        Schema::dropIfExists('api_upload_assets');
    }
};

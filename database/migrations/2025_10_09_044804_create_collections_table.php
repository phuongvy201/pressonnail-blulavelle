<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();

            // Owner
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('shop_id')
                ->nullable()
                ->constrained()
                ->onDelete('cascade');

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();

            // Collection type
            $table->enum('type', ['manual', 'automatic'])
                ->default('manual');

            $table->json('auto_rules')->nullable();

            // Status
            $table->enum('status', ['active', 'inactive', 'draft'])
                ->default('active');

            $table->integer('sort_order')->default(0);
            $table->boolean('featured')->default(false);

            // Admin control
            $table->boolean('admin_approved')->default(false);
            $table->text('admin_notes')->nullable();

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['status', 'featured']);
            $table->index(['user_id', 'status']);
            $table->index(['shop_id', 'admin_approved']);
            $table->index('admin_approved');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};

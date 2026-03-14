<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_templates', function (Blueprint $table) {
            $table->id();

            // User sở hữu template
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->onDelete('cascade');

            $table->string('name');
            $table->unsignedBigInteger('category_id');
            $table->decimal('base_price', 10, 2);
            $table->text('description')->nullable();

            // Customization
            $table->boolean('allow_customization')->default(false);
            $table->json('customizations')->nullable();

            // Media
            $table->json('media')->nullable(); // Lưu array media (image/video)

            $table->timestamps();

            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_templates');
    }
};

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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // Quan hệ
            $table->foreignId('template_id')
                  ->constrained('product_templates')
                  ->onDelete('cascade');

            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained()
                  ->onDelete('cascade');

            $table->foreignId('shop_id')
                  ->nullable()
                  ->constrained()
                  ->onDelete('cascade');

            // Thông tin cơ bản
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique()->nullable();

            $table->decimal('price', 10, 2)->nullable(); 
            $table->text('description')->nullable();
            $table->json('media')->nullable();
            $table->integer('quantity')->default(0);

            $table->enum('status', ['active', 'inactive', 'draft'])
                  ->default('active');

            // Tracking
            $table->string('created_by')->nullable();
            $table->unsignedBigInteger('api_token_id')->nullable();

            // Feed / Marketing fields
            $table->string('google_product_category', 200)->nullable();
            $table->string('fb_product_category', 200)->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('color', 200)->nullable();
            $table->string('age_group', 50)->nullable();
            $table->string('material', 200)->nullable();
            $table->string('pattern', 100)->nullable();
            $table->string('shipping', 200)->nullable();
            $table->string('shipping_weight', 50)->nullable();
            $table->integer('quantity_to_sell_on_facebook')
                  ->default(100);

            $table->timestamps();

            // Foreign keys
            $table->foreign('api_token_id')
                  ->references('id')
                  ->on('api_tokens')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
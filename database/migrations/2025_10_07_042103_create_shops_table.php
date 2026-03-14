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
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');

            // Basic Info
            $table->string('shop_name');
            $table->string('shop_slug')->unique();
            $table->text('shop_description')->nullable();
            $table->string('shop_logo')->nullable();
            $table->string('shop_banner')->nullable();

            // Contact Info
            $table->string('shop_phone')->nullable();
            $table->string('shop_email')->nullable();
            $table->text('shop_address')->nullable();
            $table->string('shop_city')->nullable();
            $table->string('shop_country')->default('Vietnam');

            // Shop Status & Stats
            $table->enum('shop_status', ['active', 'inactive', 'suspended'])->default('active');
            $table->boolean('verified')->default(false);
            $table->decimal('rating', 3, 2)->default(0.00); // 0.00 - 5.00
            $table->integer('total_ratings')->default(0);
            $table->integer('total_products')->default(0);
            $table->integer('total_sales')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0.00);

            // Business Info
            $table->string('business_license')->nullable();
            $table->string('tax_code')->nullable();

            // Social Links
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('website_url')->nullable();

            // Policies
            $table->text('return_policy')->nullable();
            $table->text('shipping_policy')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('shop_slug');
            $table->index('shop_status');
            $table->index('verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};

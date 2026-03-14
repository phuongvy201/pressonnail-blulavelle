<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipping_zone_id')
                ->constrained('shipping_zones')
                ->onDelete('cascade');

            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->onDelete('set null');

            $table->string('name');
            $table->text('description')->nullable();

            // Delivery time
            $table->unsignedSmallInteger('delivery_min_days')->nullable();
            $table->unsignedSmallInteger('delivery_max_days')->nullable();
            $table->string('delivery_note')->nullable();

            // Pricing
            $table->decimal('first_item_cost', 10, 2);
            $table->decimal('additional_item_cost', 10, 2)->default(0);

            // Conditions
            $table->integer('min_items')->nullable();
            $table->integer('max_items')->nullable();
            $table->decimal('min_order_value', 10, 2)->nullable();
            $table->decimal('max_order_value', 10, 2)->nullable();
            $table->decimal('max_weight', 10, 2)->nullable();

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->index(['shipping_zone_id', 'category_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};

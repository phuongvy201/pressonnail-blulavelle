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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('product_id')
                ->constrained()
                ->onDelete('cascade');

            // Snapshot product data at purchase time
            $table->string('product_name');
            $table->text('product_description')->nullable();

            $table->decimal('unit_price', 10, 2);
            $table->integer('quantity');
            $table->decimal('total_price', 10, 2);

            // Shipping per item (nếu có logic first item shipping khác)
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->boolean('is_first_item')->default(false);
            $table->text('shipping_notes')->nullable();

            // Custom options / variant attributes
            $table->json('product_options')->nullable();

            $table->timestamps();

            // Index for performance
            $table->index('order_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};

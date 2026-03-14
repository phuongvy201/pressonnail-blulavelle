<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_combos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->decimal('combo_price', 10, 2)->nullable()->comment('Tổng giá cố định cho cả bộ (nếu set thì ưu tiên hơn discount)');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_combo_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_combo_id')->constrained('product_combos')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_combo_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_combo_items');
        Schema::dropIfExists('product_combos');
    }
};

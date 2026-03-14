<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('product_combo_items');

        Schema::create('product_combo_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_combo_id')->constrained('product_combos')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_combo_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_combo_categories');

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
};

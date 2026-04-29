<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_cross_sells', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('target_category_id')->constrained('categories')->cascadeOnDelete();
            $table->unsignedTinyInteger('priority')->default(50)->comment('1 is highest priority');
            $table->timestamps();

            $table->unique(['source_category_id', 'target_category_id'], 'ux_category_cross_sell_pair');
            $table->index(['source_category_id', 'priority'], 'ix_cross_sell_source_priority');
            $table->index(['target_category_id'], 'ix_cross_sell_target_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_cross_sells');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('model', 120);
            $table->string('content_hash', 64);
            $table->longText('vector');
            $table->timestamps();
            $table->unique(['product_id', 'model']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_embeddings');
    }
};

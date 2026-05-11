<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_gift_card_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('gift_card_id')->constrained()->onDelete('cascade');
            $table->string('gift_card_code');
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->unique(['order_id', 'gift_card_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_gift_card_usages');
    }
};

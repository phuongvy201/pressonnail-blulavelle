<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('affiliate_click_events')) {
            return;
        }

        Schema::create('affiliate_click_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->string('ref_code', 64);
            $table->string('landing_path', 512)->nullable();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('utm_source', 128)->nullable();
            $table->string('utm_medium', 128)->nullable();
            $table->string('utm_campaign', 128)->nullable();
            $table->string('utm_content', 128)->nullable();
            $table->string('referrer_host', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['affiliate_id', 'created_at']);
            $table->index(['affiliate_id', 'landing_path']);
            $table->index(['affiliate_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_click_events');
    }
};

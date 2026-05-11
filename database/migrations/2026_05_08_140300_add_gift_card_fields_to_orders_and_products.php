<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'gift_card_code')) {
                $table->string('gift_card_code')->nullable()->after('promo_code');
            }
            if (!Schema::hasColumn('orders', 'gift_card_amount')) {
                $table->decimal('gift_card_amount', 10, 2)->default(0)->after('discount_amount');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'is_gift_card')) {
                $table->boolean('is_gift_card')->default(false)->after('requires_special_handling');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'gift_card_code')) {
                $table->dropColumn('gift_card_code');
            }
            if (Schema::hasColumn('orders', 'gift_card_amount')) {
                $table->dropColumn('gift_card_amount');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'is_gift_card')) {
                $table->dropColumn('is_gift_card');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'affiliate_eligible')) {
                $table->boolean('affiliate_eligible')->default(false)->after('is_gift_card');
                $table->index('affiliate_eligible');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'affiliate_eligible')) {
                $table->dropIndex(['affiliate_eligible']);
                $table->dropColumn('affiliate_eligible');
            }
        });
    }
};

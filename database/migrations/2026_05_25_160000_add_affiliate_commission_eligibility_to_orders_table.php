<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('affiliate_commission_eligibility', 16)->nullable()->after('affiliate_attribution');
            $table->string('affiliate_commission_note', 64)->nullable()->after('affiliate_commission_eligibility');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['affiliate_commission_eligibility', 'affiliate_commission_note']);
        });
    }
};

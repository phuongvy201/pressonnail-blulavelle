<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('sales_channel', 16)->default('website')->index();
        });

        if (! Schema::hasTable('checkout_attempts')) {
            return;
        }

        $appOrderIds = DB::table('checkout_attempts')
            ->whereNotNull('order_id')
            ->pluck('order_id')
            ->unique()
            ->values()
            ->all();

        if ($appOrderIds === []) {
            return;
        }

        DB::table('orders')->whereIn('id', $appOrderIds)->update(['sales_channel' => 'app']);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['sales_channel']);
            $table->dropColumn('sales_channel');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            if (! Schema::hasColumn('affiliates', 'payout_routing_number')) {
                $table->text('payout_routing_number')->nullable()->after('payout_routing_last4');
            }
            if (! Schema::hasColumn('affiliates', 'payout_account_number')) {
                $table->text('payout_account_number')->nullable()->after('payout_routing_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            foreach (['payout_account_number', 'payout_routing_number'] as $col) {
                if (Schema::hasColumn('affiliates', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

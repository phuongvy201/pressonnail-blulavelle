<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('affiliate_commissions')) {
            return;
        }

        if (! Schema::hasColumn('affiliate_commissions', 'commission_rate')) {
            Schema::table('affiliate_commissions', function (Blueprint $table) {
                $table->decimal('commission_rate', 5, 2)->default(0)->after('commission_base');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('affiliate_commissions') && Schema::hasColumn('affiliate_commissions', 'commission_rate')) {
            Schema::table('affiliate_commissions', function (Blueprint $table) {
                $table->dropColumn('commission_rate');
            });
        }
    }
};

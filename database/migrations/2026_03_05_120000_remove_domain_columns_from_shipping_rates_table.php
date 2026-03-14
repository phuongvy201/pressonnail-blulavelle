<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shipping_rates', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('shipping_rates', 'domain')) {
                $columnsToDrop[] = 'domain';
            }
            if (Schema::hasColumn('shipping_rates', 'domains')) {
                $columnsToDrop[] = 'domains';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_rates', function (Blueprint $table) {
            if (!Schema::hasColumn('shipping_rates', 'domain')) {
                $table->string('domain')->nullable()->after('category_id');
            }
            if (!Schema::hasColumn('shipping_rates', 'domains')) {
                $table->json('domains')->nullable()->after('domain');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('affiliates')) {
            return;
        }

        Schema::table('affiliates', function (Blueprint $table) {
            if (!Schema::hasColumn('affiliates', 'tier')) {
                $table->string('tier', 32)->default('basic');
            }
            if (!Schema::hasColumn('affiliates', 'commission_rate_override')) {
                $table->decimal('commission_rate_override', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('affiliates', 'tier_locked')) {
                $table->boolean('tier_locked')->default(false);
            }
            if (!Schema::hasColumn('affiliates', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('affiliates')) {
            return;
        }

        Schema::table('affiliates', function (Blueprint $table) {
            foreach (['tier', 'commission_rate_override', 'tier_locked', 'is_active'] as $col) {
                if (Schema::hasColumn('affiliates', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

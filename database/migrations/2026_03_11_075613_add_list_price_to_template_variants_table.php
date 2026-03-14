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
        Schema::table('template_variants', function (Blueprint $table) {
            if (!Schema::hasColumn('template_variants', 'list_price')) {
                $table->decimal('list_price', 10, 2)->nullable()->after('price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('template_variants', function (Blueprint $table) {
            if (Schema::hasColumn('template_variants', 'list_price')) {
                $table->dropColumn('list_price');
            }
        });
    }
};

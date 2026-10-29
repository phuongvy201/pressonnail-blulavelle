<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('lookup_token', 64)->nullable()->unique()->after('order_number');
        });

        DB::table('orders')->whereNull('lookup_token')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                DB::table('orders')->where('id', $row->id)->update([
                    'lookup_token' => Str::lower(Str::random(48)),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['lookup_token']);
            $table->dropColumn('lookup_token');
        });
    }
};

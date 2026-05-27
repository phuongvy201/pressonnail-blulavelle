<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('affiliates')->where('tier', 'medium')->update(['tier' => 'silver']);
        DB::table('affiliates')->where('tier', 'high')->update(['tier' => 'gold']);

        if (\Illuminate\Support\Facades\Schema::hasTable('products') && \Illuminate\Support\Facades\Schema::hasColumn('products', 'sample_min_tier')) {
            DB::table('products')->where('sample_min_tier', 'medium')->update(['sample_min_tier' => 'silver']);
            DB::table('products')->where('sample_min_tier', 'high')->update(['sample_min_tier' => 'gold']);
        }
    }

    public function down(): void
    {
        DB::table('affiliates')->where('tier', 'silver')->update(['tier' => 'medium']);
        DB::table('affiliates')->where('tier', 'gold')->update(['tier' => 'high']);

        if (\Illuminate\Support\Facades\Schema::hasTable('products') && \Illuminate\Support\Facades\Schema::hasColumn('products', 'sample_min_tier')) {
            DB::table('products')->where('sample_min_tier', 'silver')->update(['sample_min_tier' => 'medium']);
            DB::table('products')->where('sample_min_tier', 'gold')->update(['sample_min_tier' => 'high']);
        }
    }
};

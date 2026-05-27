<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'sample_request_enabled')) {
                $table->boolean('sample_request_enabled')->default(false)->after('affiliate_eligible');
                $table->string('sample_min_tier', 16)->nullable()->after('sample_request_enabled');
                $table->unsignedTinyInteger('sample_max_quantity_per_request')->nullable()->after('sample_min_tier');
                $table->unsignedSmallInteger('sample_quota_per_affiliate')->nullable()->after('sample_max_quantity_per_request');
                $table->boolean('sample_requires_approval')->default(true)->after('sample_quota_per_affiliate');

                $table->index('sample_request_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'sample_request_enabled')) {
                $table->dropIndex(['sample_request_enabled']);
                $table->dropColumn([
                    'sample_request_enabled',
                    'sample_min_tier',
                    'sample_max_quantity_per_request',
                    'sample_quota_per_affiliate',
                    'sample_requires_approval',
                ]);
            }
        });
    }
};

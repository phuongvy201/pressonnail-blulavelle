<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliate_applications', function (Blueprint $table) {
            $table->string('primary_platform', 32)->nullable()->after('phone');
            $table->string('follower_range', 32)->nullable()->after('primary_platform');
            $table->unsignedInteger('follower_count')->nullable()->after('follower_range');
            $table->string('content_niche', 255)->nullable()->after('follower_count');
            $table->text('portfolio_links')->nullable()->after('social_links');
            $table->foreignId('reviewed_by')->nullable()->after('processed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('affiliate_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn([
                'primary_platform',
                'follower_range',
                'follower_count',
                'content_niche',
                'portfolio_links',
            ]);
        });
    }
};

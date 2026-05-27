<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            if (! Schema::hasColumn('affiliates', 'phone')) {
                $table->string('phone', 32)->nullable()->after('display_name');
            }
            if (! Schema::hasColumn('affiliates', 'primary_platform')) {
                $table->string('primary_platform', 32)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('affiliates', 'follower_range')) {
                $table->string('follower_range', 32)->nullable()->after('primary_platform');
            }
            if (! Schema::hasColumn('affiliates', 'content_niche')) {
                $table->string('content_niche', 255)->nullable()->after('follower_range');
            }
            if (! Schema::hasColumn('affiliates', 'social_links')) {
                $table->text('social_links')->nullable()->after('content_niche');
            }
            if (! Schema::hasColumn('affiliates', 'portfolio_links')) {
                $table->text('portfolio_links')->nullable()->after('social_links');
            }
            if (! Schema::hasColumn('affiliates', 'payout_method')) {
                $table->string('payout_method', 32)->nullable()->after('portfolio_links');
            }
            if (! Schema::hasColumn('affiliates', 'payout_legal_name')) {
                $table->string('payout_legal_name', 255)->nullable()->after('payout_method');
            }
            if (! Schema::hasColumn('affiliates', 'payout_paypal_email')) {
                $table->string('payout_paypal_email', 255)->nullable()->after('payout_legal_name');
            }
            if (! Schema::hasColumn('affiliates', 'payout_venmo_handle')) {
                $table->string('payout_venmo_handle', 128)->nullable()->after('payout_paypal_email');
            }
            if (! Schema::hasColumn('affiliates', 'payout_bank_name')) {
                $table->string('payout_bank_name', 255)->nullable()->after('payout_venmo_handle');
            }
            if (! Schema::hasColumn('affiliates', 'payout_account_holder')) {
                $table->string('payout_account_holder', 255)->nullable()->after('payout_bank_name');
            }
            if (! Schema::hasColumn('affiliates', 'payout_account_last4')) {
                $table->string('payout_account_last4', 4)->nullable()->after('payout_account_holder');
            }
            if (! Schema::hasColumn('affiliates', 'payout_routing_last4')) {
                $table->string('payout_routing_last4', 4)->nullable()->after('payout_account_last4');
            }
            if (! Schema::hasColumn('affiliates', 'payout_setup_completed_at')) {
                $table->timestamp('payout_setup_completed_at')->nullable()->after('payout_routing_last4');
            }
        });
    }

    public function down(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            $cols = [
                'phone', 'primary_platform', 'follower_range', 'content_niche',
                'social_links', 'portfolio_links', 'payout_method', 'payout_legal_name',
                'payout_paypal_email', 'payout_venmo_handle', 'payout_bank_name',
                'payout_account_holder', 'payout_account_last4', 'payout_routing_last4',
                'payout_setup_completed_at',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('affiliates', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

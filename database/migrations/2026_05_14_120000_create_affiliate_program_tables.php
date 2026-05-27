<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('affiliates')) {
            Schema::create('affiliates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('code', 64)->unique();
                $table->string('display_name')->nullable();
                $table->string('tier', 32)->default('basic');
                $table->decimal('commission_rate_override', 5, 2)->nullable()->comment('Admin override %; null uses tier rate');
                $table->boolean('tier_locked')->default(false)->comment('When true, auto tier changes are disabled');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        Schema::table('promo_codes', function (Blueprint $table) {
            if (!Schema::hasColumn('promo_codes', 'affiliate_id')) {
                $table->foreignId('affiliate_id')->nullable()->after('is_active')->constrained('affiliates')->nullOnDelete();
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'affiliate_id')) {
                $table->foreignId('affiliate_id')->nullable()->after('user_id')->constrained('affiliates')->nullOnDelete();
            }
            if (!Schema::hasColumn('orders', 'affiliate_attribution')) {
                $table->string('affiliate_attribution', 32)->nullable()->after('affiliate_id');
            }
            if (!Schema::hasColumn('orders', 'utm_snapshot')) {
                $table->json('utm_snapshot')->nullable()->after('affiliate_attribution');
            }
        });

        if (!Schema::hasTable('affiliate_commissions')) {
            Schema::create('affiliate_commissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
                $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
                $table->decimal('commission_base', 12, 2);
                $table->decimal('commission_rate', 5, 2);
                $table->decimal('commission_amount', 12, 2);
                $table->decimal('original_commission_base', 12, 2)->nullable();
                $table->decimal('original_commission_amount', 12, 2)->nullable();
                $table->string('currency', 3);
                $table->string('status', 32)->default('pending');
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
            });
        } else {
            if (!Schema::hasColumn('affiliate_commissions', 'commission_rate')) {
                Schema::table('affiliate_commissions', function (Blueprint $table) {
                    $table->decimal('commission_rate', 5, 2)->default(0)->after('commission_base');
                });
            }
            if (!Schema::hasColumn('affiliate_commissions', 'original_commission_base')) {
                Schema::table('affiliate_commissions', function (Blueprint $table) {
                    $table->decimal('original_commission_base', 12, 2)->nullable()->after('commission_amount');
                });
            }
            if (!Schema::hasColumn('affiliate_commissions', 'original_commission_amount')) {
                Schema::table('affiliate_commissions', function (Blueprint $table) {
                    $after = Schema::hasColumn('affiliate_commissions', 'original_commission_base')
                        ? 'original_commission_base'
                        : 'commission_amount';
                    $table->decimal('original_commission_amount', 12, 2)->nullable()->after($after);
                });
            }
        }

        if (!Schema::hasTable('affiliate_balance_adjustments')) {
            Schema::create('affiliate_balance_adjustments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->decimal('amount', 12, 2)->comment('Negative = clawback');
                $table->string('type', 32)->default('clawback');
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_balance_adjustments');
        Schema::dropIfExists('affiliate_commissions');

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (Schema::hasColumn('orders', 'affiliate_id')) {
                    $table->dropForeign(['affiliate_id']);
                }
                $drops = [];
                foreach (['affiliate_id', 'affiliate_attribution', 'utm_snapshot'] as $col) {
                    if (Schema::hasColumn('orders', $col)) {
                        $drops[] = $col;
                    }
                }
                if ($drops !== []) {
                    $table->dropColumn($drops);
                }
            });
        }

        if (Schema::hasTable('promo_codes') && Schema::hasColumn('promo_codes', 'affiliate_id')) {
            Schema::table('promo_codes', function (Blueprint $table) {
                $table->dropForeign(['affiliate_id']);
                $table->dropColumn('affiliate_id');
            });
        }

        Schema::dropIfExists('affiliates');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promo_codes', function (Blueprint $table) {
            $table->string('send_on_trigger', 32)->nullable()->after('is_active')
                ->comment('thank_you, wishlist, add_to_cart - khi set sẽ gửi mã này qua email khi trigger xảy ra');
        });
    }

    public function down(): void
    {
        Schema::table('promo_codes', function (Blueprint $table) {
            $table->dropColumn('send_on_trigger');
        });
    }
};

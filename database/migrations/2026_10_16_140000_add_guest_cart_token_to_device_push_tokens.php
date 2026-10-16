<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_push_tokens', function (Blueprint $table) {
            $table->string('guest_cart_token', 64)->nullable()->after('guest_device_key')->index();
        });
    }

    public function down(): void
    {
        Schema::table('device_push_tokens', function (Blueprint $table) {
            $table->dropColumn('guest_cart_token');
        });
    }
};

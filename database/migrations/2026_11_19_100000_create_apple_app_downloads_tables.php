<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apple_app_downloads', function (Blueprint $table) {
            $table->id();
            $table->date('stat_date');
            $table->string('download_type', 80)->default('');
            $table->string('source_type', 80)->default('');
            $table->string('campaign', 120)->default('');
            $table->string('territory', 8)->default('');
            $table->string('device', 40)->default('');
            $table->unsignedInteger('counts')->default(0);
            $table->timestamps();

            $table->unique(
                ['stat_date', 'download_type', 'source_type', 'campaign', 'territory', 'device'],
                'apple_downloads_dimensions_unique'
            );
            $table->index('campaign');
        });

        Schema::create('apple_analytics_imports', function (Blueprint $table) {
            $table->id();
            $table->string('instance_id')->unique();
            $table->string('report_name');
            $table->date('processing_date')->nullable();
            $table->string('checksum', 64);
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apple_analytics_imports');
        Schema::dropIfExists('apple_app_downloads');
    }
};

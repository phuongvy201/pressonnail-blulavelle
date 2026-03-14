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
        Schema::create('template_variants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('template_id')
                ->constrained('product_templates')
                ->onDelete('cascade');

            $table->string('variant_name');
            // Example: "Army Green - S"

            // Dynamic attributes
            $table->json('attributes')->nullable();
            /*
                Example:
                {
                    "size": "S",
                    "color": "Army Green",
                    "material": "Cotton",
                    "finish": "Glossy"
                }
            */

            $table->decimal('price', 10, 2)->nullable();
            $table->integer('quantity')->default(0);
            $table->json('media')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_variants');
    }
};

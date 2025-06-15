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
        Schema::create('product_variant_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')
                ->constrained('product_variants') // Assuming 'product_variants' table exists
                ->onDelete('cascade');
            $table->foreignId('attribute_value_id')
                ->constrained('attribute_values') // Assuming 'attribute_values' table exists
                ->onDelete('cascade');
            $table->timestamps();

            // Define a unique constraint with a custom, shorter name
            $table->unique(
                ['product_variant_id', 'attribute_value_id'],
                'prod_variant_attr_val_unique'
            );

            // $table->unique(['product_variant_id', 'attribute_value_id'], 'pv_av_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variant_attribute_values');
    }
};

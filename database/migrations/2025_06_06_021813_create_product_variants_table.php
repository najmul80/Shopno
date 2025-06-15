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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade'); // Foreign key to the parent product
            $table->string('sku')->unique()->nullable(); // SKU specific to this variant, globally unique or unique per store
            $table->string('name_suffix')->nullable(); // e.g., "Red, Small" - to append to product name for display
            $table->decimal('additional_price', 10, 2)->default(0.00); // Price difference from base product, can be negative or positive
            // Or, store absolute sale_price here.
            $table->decimal('sale_price', 10, 2)->nullable(); // Absolute sale price for this variant (overrides product base price + additional_price logic)
            $table->decimal('purchase_price', 10, 2)->nullable(); // Purchase price for this variant
            $table->integer('stock_quantity')->default(0);
            $table->integer('low_stock_threshold')->nullable();
            $table->string('barcode')->nullable()->unique(); // Barcode specific to this variant
            $table->string('image_path')->nullable(); // Path to a specific image for this variant
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // If SKU should be unique per store (assuming Product model has store_id)
            // This requires joining with products table or having store_id here too.
            // For now, making SKU globally unique.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};

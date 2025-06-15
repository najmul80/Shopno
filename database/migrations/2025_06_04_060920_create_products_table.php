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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->onDelete('cascade'); // If store is deleted, its products are also deleted
            $table->foreignId('category_id')
                ->constrained('categories')
                ->onDelete('cascade'); // If category is deleted, its products are also deleted (consider restrict or set null based on logic)
            $table->string('name');
            $table->string('slug')->unique(); // SEO-friendly URL, unique globally
            $table->text('description')->nullable();
            $table->string('sku')->nullable(); // Stock Keeping Unit, should be unique per store
            $table->decimal('purchase_price', 10, 2)->nullable()->default(0.00); // Price at which the store bought the product
            $table->decimal('sale_price', 10, 2); // Price at which the product is sold
            $table->integer('stock_quantity')->default(0);
            $table->integer('low_stock_threshold')->nullable()->default(5); // Alert when stock drops to this level
            $table->string('unit')->nullable()->comment('e.g., pcs, kg, ltr, box'); // Unit of measurement
            $table->boolean('is_active')->default(true); // To activate/deactivate a product
            $table->boolean('is_featured')->default(false); // To mark as a featured product
            $table->json('attributes')->nullable(); // For storing additional product attributes like size, color, etc.
            $table->timestamps();
            $table->softDeletes();

            // SKU should be unique within a specific store
            $table->unique(['store_id', 'sku']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

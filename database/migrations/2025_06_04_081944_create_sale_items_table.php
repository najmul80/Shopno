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
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade'); // Link to the sales table
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict'); // Link to products, restrict deletion if product is in a sale
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2); // Price per unit at the time of sale
            $table->decimal('item_sub_total', 12, 2); // quantity * unit_price for this item
            // $table->decimal('item_discount_amount', 10, 2)->default(0.00); // If specific discount per item
            // $table->decimal('item_tax_amount', 10, 2)->default(0.00); // If specific tax per item
            // $table->decimal('item_total_amount', 12, 2); // Final amount for this item line
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};

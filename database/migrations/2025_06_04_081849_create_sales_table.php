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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->foreignId('user_id')->comment('Staff who made the sale')->constrained('users')->onDelete('restrict');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->decimal('sub_total', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->string('discount_type')->nullable()->comment('e.g., percentage, fixed');
            $table->decimal('tax_percentage', 5, 2)->nullable()->default(0.00);
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->decimal('shipping_charge', 10, 2)->default(0.00);
            $table->decimal('grand_total', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0.00);
            $table->decimal('change_returned', 10, 2)->default(0.00);
            $table->string('payment_method')->default('cash');
            $table->string('payment_status')->default('pending');
            $table->string('sale_status')->default('completed');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};

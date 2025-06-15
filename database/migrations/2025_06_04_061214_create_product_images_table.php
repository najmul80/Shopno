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
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products') // Foreign key to products table
                ->onDelete('cascade');    // If a product is deleted, its images are also deleted
            $table->string('image_path');         // Path to the image file in storage
            $table->string('caption')->nullable(); // Optional caption for the image
            $table->boolean('is_primary')->default(false); // To mark one image as the primary/featured image
            $table->integer('sort_order')->default(0); // For custom sorting of images
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};

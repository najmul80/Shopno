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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')
                ->constrained('stores') // Foreign key to stores table
                ->onDelete('cascade');   // If a store is deleted, its categories are also deleted
            $table->string('name');
            $table->string('slug')->unique(); // SEO-friendly URL string, unique across all categories
            $table->text('description')->nullable();
            $table->foreignId('parent_id') // For parent-child category structure (sub-categories)
                ->nullable()
                ->constrained('categories') // Self-referencing foreign key
                ->onDelete('cascade');    // If a parent category is deleted, its children are also deleted
            $table->string('image_path')->nullable(); // Path to category image
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0); // For custom sorting of categories
            $table->timestamps();
            $table->softDeletes();

            // A category name should be unique within the same store and for the same parent_id
            // $table->unique(['store_id', 'parent_id', 'name']); // More complex unique constraint
            // For simplicity, we'll rely on slug for overall uniqueness for now, and name can be unique per store later if needed in controller logic.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};

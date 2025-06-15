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
        Schema::create('stores', function (Blueprint $table) {
            $table->id(); 
            $table->string('name')->unique(); // Store name, must be unique
            $table->text('description')->nullable(); 
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state_province')->nullable(); // State or Province
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable()->unique(); // Optional store contact email, unique if provided
            $table->string('website')->nullable();
            $table->string('logo_path')->nullable(); // Path to the store's logo image
            $table->boolean('is_active')->default(true); // To activate/deactivate a store
            // $table->foreignId('owner_id')->nullable()->constrained('users')->onDelete('set null'); // Optional: if a store has a specific owner user
            $table->timestamps(); // created_at and updated_at
            $table->softDeletes(); // Adds deleted_at column for soft deletes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};

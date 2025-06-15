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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->onDelete('cascade'); // If store is deleted, its customers are also deleted
            $table->string('name');
            $table->string('email')->nullable()->unique(); // Email can be optional but must be unique if provided
            $table->string('phone_number')->nullable()->unique(); // Phone number can be optional but must be unique if provided
            $table->text('address_line1')->nullable();
            $table->text('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state_province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            $table->string('photo_path')->nullable(); // Path to customer's photo
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->text('notes')->nullable(); // Any additional notes about the customer
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes(); // For soft deleting customers

            // A customer (identified by email or phone) should be unique per store,
            // but email/phone themselves are globally unique in this setup.
            // If you want email/phone to be unique PER STORE only, the unique constraint needs adjustment,
            // and you'd likely remove the global unique() from email/phone above and add a composite unique key.
            // $table->unique(['store_id', 'email']);
            // $table->unique(['store_id', 'phone_number']);
            // For simplicity now, email and phone_number are globally unique if provided.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};

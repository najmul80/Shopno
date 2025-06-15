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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('store_id')
                ->nullable() // store_id can be null (e.g., for super admins not tied to a specific store)
                ->after('password') // Position of the column in the table
                ->constrained('stores') // Foreign key constraint to the 'id' column on the 'stores' table
                ->onDelete('set null'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_id');
        });
    }
};

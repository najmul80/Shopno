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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable() // Null for global settings, store_id for store-specific
                ->constrained('stores')
                ->onDelete('cascade'); // If a store is deleted, its specific settings are also deleted

            $table->string('group')->default('general')->index(); // Group for settings, e.g., 'general', 'invoice', 'notifications', 'appearance'
            $table->string('key')->index(); // The setting key, e.g., 'app_name', 'currency_symbol', 'low_stock_alert_email'
            $table->text('value')->nullable(); // The setting value, stored as text (can be JSON for arrays/objects)
            $table->string('type')->default('string'); // Data type of the value, e.g., 'string', 'boolean', 'integer', 'array', 'json'
            $table->text('description')->nullable(); // Optional description of the setting
            $table->boolean('is_public')->default(false); // Whether this setting can be exposed to non-admin users (rarely true)
            $table->timestamps();

            // Ensure key is unique within a store/group or globally if store_id is null
            // If store_id is null, key+group must be unique.
            // If store_id is not null, key+group+store_id must be unique.
            // This complex unique constraint is harder to define directly in migration for nullable fields.
            // We can handle uniqueness check in service/controller or use a more complex raw SQL unique index if needed.
            // For now, let's make (group, key, store_id) unique.
            // Note: MySQL unique constraints treat NULLs as distinct values.
            // To make key unique globally when store_id is NULL, and unique per store when store_id is not NULL,
            // you might need application-level validation or a generated column for the unique constraint.
            // A simpler approach for now:
            $table->unique(['key', 'group', 'store_id'], 'settings_key_group_store_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};

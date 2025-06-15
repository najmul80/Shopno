<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Services\Settings\SettingService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DefaultSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
     public function run(SettingService $settingService): void // Inject SettingService
    {
        // Global Settings
        $settingService->set('app_name', 'My POS App', null, 'general', 'string', 'The main name of the application.');
        $settingService->set('default_currency_symbol', '$', null, 'localisation', 'string', 'Default currency symbol.');
        $settingService->set('low_stock_notification_enabled', true, null, 'notifications', 'boolean', 'Enable low stock email alerts for admins.');

        // Example: Create a default store if none exists for store-specific settings example
        $store1 = Store::firstOrCreate(['name' => 'Main Branch'], ['city' => 'Anytown']);

        // Store Specific Settings for Store 1
        $settingService->set('invoice_prefix', 'INV-MB-', $store1->id, 'invoice', 'string', 'Prefix for invoices in this store.');
        $settingService->set('store_specific_tax_rate', 5.5, $store1->id, 'tax', 'float', 'Specific tax rate for this store.');
    }
}

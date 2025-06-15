<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\Settings\SettingService; // Import the service
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class SettingController extends BaseApiController
{
    protected SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
        $this->middleware(['auth:api', 'permission:view global_settings'])->only(['index']);
        $this->middleware(['auth:api', 'permission:manage global_settings'])->only(['updateMultiple']);
    }

    /**
     * Get all global settings, optionally filtered by group.
     */
    public function index(Request $request)
    {
        try {
            $group = $request->input('group'); // e.g., ?group=general
            $settings = $this->settingService->getAllSettings(null, $group); // null for storeId means global
            return $this->successResponse($settings, 'Global settings fetched successfully.');
        } catch (Exception $e) {
            Log::error('Error fetching global settings: ' . $e->getMessage());
            return $this->errorResponse('Could not fetch global settings.', 500);
        }
    }

    /**
     * Update multiple global settings.
     * Expects data in format: { "group1": { "key1": "value1", "key2": true }, "group2": { "key3": 123 } }
     * Or flat: { "app_name": "New Name", "default_currency_symbol": "€" } (service needs to handle group if flat)
     * The service's updateMultipleSettings expects group structure, or needs adjustment for flat key-value.
     * For simplicity, let's assume client sends with group, and service expects it.
     */
    public function updateMultiple(Request $request)
    {
        // Validation for incoming settings data structure
        // Example: Each key in the request should be a group, and its value an array of key-value settings
        $validatedData = $request->validate([
            '*' => 'array', // Each top-level key (group) must be an array
            '*.*' => 'sometimes', // Each setting value can be of mixed type (service handles casting)
            // More specific validation based on known setting keys and types can be added.
            // 'general.app_name' => 'sometimes|string|max:255',
            // 'localisation.default_currency_symbol' => 'sometimes|string|max:5',
            // 'notifications.low_stock_notification_enabled' => 'sometimes|boolean',
        ]);

        try {
            // The SettingService's updateMultipleSettings expects settingsData like:
            // [ 'groupName' => [ 'keyName' => ['value' => ..., 'type' => ..., 'description' => ... (optional)], ... ], ... ]
            // Or if simpler, [ 'groupName' => [ 'keyName' => value, ... ] ] and service infers type/desc
            // For this example, let's assume request sends: { "group": { "key": "value" } }
            // And we assume type and description are already known or not updated here.

            foreach ($validatedData as $group => $settingsInGroup) {
                foreach ($settingsInGroup as $key => $value) {
                     // Find existing setting to get its type, or default to 'string'
                    $existingSetting = \App\Models\Setting::where('key', $key)->where('group', $group)->whereNull('store_id')->first();
                    $type = $existingSetting->type ?? 'string'; // Default to string if new or type not sent
                    $description = $existingSetting->description ?? null;
                    // Update the setting
                    $this->settingService->set($key, $value, null, $group, $type, $description);
                }
            }

            return $this->successResponse(null, 'Global settings updated successfully.');
        } catch (Exception $e) {
            Log::error('Error updating global settings: ' . $e->getMessage(), ['request_data' => $request->all()]);
            return $this->errorResponse('Could not update global settings.', 500);
        }
    }
}
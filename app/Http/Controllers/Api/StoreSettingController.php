<?php
namespace App\Http\Controllers\Api; // Correct namespace if you put it directly in Api

use App\Http\Controllers\Api\BaseApiController;
use App\Services\Settings\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class StoreSettingController extends BaseApiController
{
    protected SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
        $this->middleware('auth:api');
        // Store admin needs 'view own_store_settings' or 'manage own_store_settings'
        $this->middleware('permission:view own_store_settings')->only(['index']);
        $this->middleware('permission:manage own_store_settings')->only(['updateMultiple']);
    }

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user->store_id) {
                return $this->forbiddenResponse('You are not associated with a store to view settings.');
            }
            $group = $request->input('group');
            $settings = $this->settingService->getAllSettings($user->store_id, $group);
            return $this->successResponse($settings, 'Store settings fetched successfully.');
        } catch (Exception $e) {
            Log::error("Error fetching settings for store ID {$user->store_id}: " . $e->getMessage());
            return $this->errorResponse('Could not fetch store settings.', 500);
        }
    }

    public function updateMultiple(Request $request)
    {
        $user = Auth::user();
        if (!$user->store_id) {
            return $this->forbiddenResponse('You are not associated with a store to update settings.');
        }

        $validatedData = $request->validate([
            '*' => 'array',
            '*.*' => 'sometimes',
            // Example specific validation for a store setting
            // 'invoice.invoice_prefix' => 'sometimes|string|max:10',
            // 'tax.store_specific_tax_rate' => 'sometimes|numeric|min:0|max:100',
        ]);

        try {
            foreach ($validatedData as $group => $settingsInGroup) {
                foreach ($settingsInGroup as $key => $value) {
                    $existingSetting = \App\Models\Setting::where('key', $key)
                                        ->where('group', $group)
                                        ->where('store_id', $user->store_id)
                                        ->first();
                    $type = $existingSetting->type ?? 'string';
                    $description = $existingSetting->description ?? null;
                    $this->settingService->set($key, $value, $user->store_id, $group, $type, $description);
                }
            }
            return $this->successResponse(null, 'Store settings updated successfully.');
        } catch (Exception $e) {
            Log::error("Error updating settings for store ID {$user->store_id}: " . $e->getMessage(), ['request_data' => $request->all()]);
            return $this->errorResponse('Could not update store settings.', 500);
        }
    }
}
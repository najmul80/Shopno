<?php
namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache; // For caching settings
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettingService
{
    /**
     * Get a setting value.
     *
     * @param string $key The setting key.
     * @param int|null $storeId Null for global, store ID for store-specific.
     * @param string $group The setting group.
     * @param mixed $default Default value if setting is not found.
     * @return mixed
     */
    public function get(string $key, ?int $storeId = null, string $group = 'general', $default = null)
    {
        $cacheKey = $this->generateCacheKey($key, $storeId, $group);

        // Attempt to get from cache first
        // return Cache::rememberForever($cacheKey, function () use ($key, $storeId, $group, $default) {
            $query = Setting::where('key', $key)->where('group', $group);
            $query = $storeId ? $query->where('store_id', $storeId) : $query->whereNull('store_id');
            $setting = $query->first();

            return $setting ? $setting->casted_value : $default;
        // });
        // Caching can be added later if performance becomes an issue. For now, direct DB access.
    }

    /**
     * Set (create or update) a setting value.
     *
     * @param string $key The setting key.
     * @param mixed $value The value to set.
     * @param int|null $storeId Null for global, store ID for store-specific.
     * @param string $group The setting group.
     * @param string $type The data type of the value.
     * @param string|null $description Optional description for the setting.
     * @return Setting
     */
    public function set(string $key, $value, ?int $storeId = null, string $group = 'general', string $type = 'string', ?string $description = null): Setting
    {
        $setting = Setting::updateOrCreate(
            [ // Conditions to find the setting
                'key' => $key,
                'group' => $group,
                'store_id' => $storeId,
            ],
            [ // Values to update or create with
                'type' => $type,
                'description' => $description,
                // 'value' will be set using the mutator 'setCastedValueAttribute'
            ]
        );

        // Use the mutator to set the value correctly based on type
        $setting->casted_value = $value;
        $setting->save();

        // Clear cache for this setting
        // Cache::forget($this->generateCacheKey($key, $storeId, $group));

        return $setting;
    }

    /**
     * Get all settings for a specific scope (global or store-specific) and optionally a group.
     * Returns an associative array of key => casted_value.
     */
    public function getAllSettings(?int $storeId = null, ?string $group = null): array
    {
        $query = Setting::query();
        $query = $storeId ? $query->where('store_id', $storeId) : $query->whereNull('store_id');

        if ($group) {
            $query->where('group', $group);
        }

        $settingsModels = $query->get();
        $settingsArray = [];
        foreach ($settingsModels as $setting) {
            $settingsArray[$setting->group][$setting->key] = $setting->casted_value;
            // Or just $settingsArray[$setting->key] = $setting->casted_value; if not grouping in output
        }
        return $settingsArray;
    }

    /**
     * Update multiple settings at once.
     * Input $settingsData should be an array like:
     * [ 'group1' => ['key1' => 'value1', 'key2' => 'value2'], 'group2' => ['key3' => 'value3'] ]
     * Or a flat array if groups are handled differently: ['key1' => 'value1']
     */
    public function updateMultipleSettings(array $settingsData, ?int $storeId = null): void
    {
        DB::transaction(function () use ($settingsData, $storeId) {
            foreach ($settingsData as $group => $keysAndValues) {
                if (is_array($keysAndValues)) {
                    foreach ($keysAndValues as $key => $settingInput) {
                        // Assuming $settingInput is ['value' => ..., 'type' => ..., 'description' => ...]
                        // Or simpler: $key => $value, and type/description are predefined or not updated here.
                        if (is_array($settingInput) && isset($settingInput['value'])) {
                            $this->set($key, $settingInput['value'], $storeId, $group, $settingInput['type'] ?? 'string', $settingInput['description'] ?? null);
                        } elseif (!is_array($settingInput)) {
                            // If just key => value is passed, assume type 'string' or get existing type
                            $existingSetting = Setting::where('key', $key)->where('group', $group)
                                ->where(fn($q) => $storeId ? $q->where('store_id', $storeId) : $q->whereNull('store_id'))
                                ->first();
                            $type = $existingSetting->type ?? 'string';
                            $description = $existingSetting->description ?? null;
                            $this->set($key, $settingInput, $storeId, $group, $type, $description);
                        }
                    }
                }
            }
        });
    }


    protected function generateCacheKey(string $key, ?int $storeId = null, string $group = 'general'): string
    {
        $scope = $storeId ? "store_{$storeId}" : 'global';
        return "setting_{$scope}_{$group}_{$key}";
    }
}
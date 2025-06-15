<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'store_id',
        'group',
        'key',
        'value',
        'type',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        // 'value' will be cast dynamically in the SettingService based on 'type'
    ];

    /**
     * Scope a query to only include global settings.
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('store_id');
    }

    /**
     * Scope a query to only include settings for a specific store.
     */
    public function scopeForStore($query, int $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    /**
     * Get the store that owns the setting (if it's a store-specific setting).
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Accessor to get the casted value based on the 'type' column.
     * Example usage: $setting->casted_value
     */
    public function getCastedValueAttribute()
    {
        if (is_null($this->value)) {
            return null;
        }

        return match (strtolower($this->type)) {
            'boolean', 'bool' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer', 'int' => (int) $this->value,
            'float', 'double', 'decimal' => (float) $this->value,
            'array', 'json' => json_decode($this->value, true) ?? [], // Return empty array if decode fails or value is null
            'object' => json_decode($this->value, false) ?? new \stdClass(),
            'date' => \Illuminate\Support\Carbon::parse($this->value)->toDateString(),
            'datetime' => \Illuminate\Support\Carbon::parse($this->value)->toDateTimeString(),
            default => (string) $this->value, // Default to string
        };
    }

    /**
     * Mutator to set the value, preparing it for storage.
     * Example usage: $setting->casted_value = ['foo' => 'bar'];
     */
    public function setCastedValueAttribute($valueToSet)
    {
        if (is_null($valueToSet)) {
            $this->attributes['value'] = null;
            return;
        }

        switch (strtolower($this->type)) {
            case 'array':
            case 'json':
            case 'object':
                $this->attributes['value'] = json_encode($valueToSet);
                break;
            case 'boolean':
            case 'bool':
                $this->attributes['value'] = filter_var($valueToSet, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
                break;
            default:
                $this->attributes['value'] = (string) $valueToSet;
        }
    }
}

<?php

namespace App\Models; // Ensure correct namespace

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id',
        'name',
        'email',
        'phone_number',
        'address_line1',
        'address_line2',
        'city',
        'state_province',
        'postal_code',
        'country',
        'photo_path',
        'date_of_birth',
        'gender',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'date_of_birth' => 'date', // Cast date_of_birth to Carbon date instance
    ];

    protected $appends = [
        'photo_url',
    ];

    /**
     * Accessor for the customer photo URL.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->photo_path) {
            $disk = config('filesystems.default_public_disk', 'public');
            try {
                return Storage::disk($disk)->url($this->photo_path);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Error generating photo URL for customer {$this->id}: " . $e->getMessage());
                return $this->getDefaultPhotoUrl();
            }
        }
        return $this->getDefaultPhotoUrl();
    }

    protected function getDefaultPhotoUrl(): string
    {
        // Ensure you have a default customer image in public/images/default-customer.png
        return asset('images/default-customer.png');
    }

    /**
     * Relationship to the Store model.
     * A customer belongs to a store.
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Relationship to Sales (a customer can have many sales).
     * This will be used when Sale model is created.
     */
    // public function sales()
    // {
    //     return $this->hasMany(Sale::class);
    // }
}
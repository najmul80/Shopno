<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Store extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'address_line1',
        'address_line2',
        'city',
        'state_province',
        'postal_code',
        'country',
        'phone_number',
        'email',
        'website',
        'logo_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'logo_url',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        // ... (logo_url accessor code) ...
        if ($this->logo_path) {
            $disk = config('filesystems.default_public_disk', 'public');
            try {
                return Storage::disk($disk)->url($this->logo_path);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Error generating logo URL for store {$this->id}: " . $e->getMessage());
                return $this->getDefaultLogoUrl();
            }
        }
        return $this->getDefaultLogoUrl();
    }

    protected function getDefaultLogoUrl(): string
    {
        return asset('images/default-store-logo.png');
    }

    /**
     * A store can have many users (employees, managers).
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * A store can have many categories.
     * This is the missing method.
     */
    public function categories() // << এই মেথডটি যোগ করুন
    {
        return $this->hasMany(Category::class);
    }

    /**
     * A store can have many products.
     */
    public function products() // << এই মেথডটিও যোগ করা ভালো, কারণ এটিও ব্যবহৃত হতে পারে
    {
        return $this->hasMany(Product::class);
    }

    /**
     * A store can have many customers.
     */
    public function customers() // << এই মেথডটিও যোগ করা ভালো
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * A store can have many sales.
     */
    public function sales() // << এই মেথডটিও যোগ করা ভালো
    {
        return $this->hasMany(Sale::class);
    }
}
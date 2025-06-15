<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log; // Added for logging in accessor

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_photo_path', // Path to the image file on disk
        'store_id',
        'is_active', // Ensure this column exists in your users table migration
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean', // Cast is_active to boolean
    ];

    protected $appends = ['profile_photo_url'];

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if ($this->profile_photo_path) {
            try {
                // Use the configured 'public' disk to get the URL
                // $url = Storage::disk('public')->url($this->profile_photo_path);
                 return Storage::disk('public_direct')->url($this->profile_photo_path);
                // Log::info("Generated profile photo URL for user {$this->id}: {$url}"); // Optional debug
                return $url;
            } catch (\Exception $e) {
                Log::error("Error generating profile photo URL for user {$this->id} (path: {$this->profile_photo_path}): " . $e->getMessage());
                return asset('images/default-avatar.png'); // Fallback to default
            }
        }
        return asset('images/default-avatar.png'); // Default if no path
    }

    protected function getDefaultProfilePhotoUrl(): string // This method is not used if logic is in getProfilePhotoUrlAttribute
    {
        return asset('images/default-avatar.png');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function refreshTokens()
    {
        return $this->hasMany(RefreshToken::class); // Ensure RefreshToken model exists
    }
}
<?php

namespace App\Models; // Ensure correct namespace

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'image_path',
        'caption',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'image_url', // To easily get the full URL of the image
    ];

    /**
     * Accessor for the image URL.
     */
    public function getImageUrlAttribute(): ?string
    {
        if ($this->image_path) {
            $disk = config('filesystems.default_public_disk', 'public');
            try {
                return Storage::disk($disk)->url($this->image_path);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Error generating image URL for product image {$this->id}: " . $e->getMessage());
                return null; // Or a default placeholder image URL
            }
        }
        return null; // Or a default placeholder
    }

    /**
     * Relationship to the Product model.
     * An image belongs to a product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
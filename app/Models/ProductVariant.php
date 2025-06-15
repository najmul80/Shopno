<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'product_id', 'sku', 'name_suffix', 'additional_price', 'sale_price', 'purchase_price',
        'stock_quantity', 'low_stock_threshold', 'barcode', 'image_path', 'is_active',
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'additional_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'stock_quantity' => 'integer',
    ];
    protected $appends = ['image_url', 'effective_sale_price', 'display_name'];

    public function product() { return $this->belongsTo(Product::class); }

    public function attributeValues() {
        return $this->belongsToMany(AttributeValue::class, 'product_variant_attribute_values')
                    ->withTimestamps()->with('attribute:id,name,display_name'); // Eager load attribute details
    }

    public function getImageUrlAttribute(): ?string {
        if ($this->image_path) {
            return Storage::disk(config('filesystems.default_public_disk', 'public'))->url($this->image_path);
        }
        // Fallback to parent product's primary image if variant has no specific image
        return $this->product->primary_image_url ?? asset('images/default-product.png');
    }

    // Calculates the final sale price for the variant
    public function getEffectiveSalePriceAttribute(): float {
        if (!is_null($this->sale_price)) { // If variant has an absolute price
            return (float) $this->sale_price;
        }
        // Otherwise, base product price + additional price for variant
        return (float) ($this->product->sale_price + $this->additional_price);
    }

    // Generates a display name for the variant, e.g., "Parent Product Name (Red, Small)"
    public function getDisplayNameAttribute(): string {
        $parentProductName = $this->product->name;
        $variantAttributes = $this->attributeValues->map(function ($attrValue) {
            return $attrValue->display_value ?? $attrValue->value;
        })->implode(', ');

        $suffix = $this->name_suffix ?? $variantAttributes;
        return $parentProductName . (!empty($suffix) ? " ({$suffix})" : "");
    }

    // Helper to update stock for this variant
    public function adjustStock(int $quantityChange): bool {
        if ($this->stock_quantity + $quantityChange < 0) return false;
        $this->stock_quantity += $quantityChange;
        return $this->save();
    }
}
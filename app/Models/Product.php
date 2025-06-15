<?php

namespace App\Models; // Ensure correct namespace

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str; // For Str::slug

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id',
        'category_id',
        'name',
        'slug',
        'description',
        'sku',
        'purchase_price',
        'sale_price',
        'stock_quantity',
        'low_stock_threshold',
        'unit',
        'is_active',
        'is_featured',
        'attributes', // JSON field for additional attributes
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'attributes' => 'array', // Cast JSON column to array
    ];

    protected $appends = [
        'primary_image_url', // To get the URL of the primary image
    ];

    /**
     * Boot method to handle model events.
     * Automatically generate a slug when a product is being created or its name is changed.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
                // Ensure slug is unique
                $originalSlug = $product->slug;
                $count = 1;
                while (static::where('slug', $product->slug)->exists()) {
                    $product->slug = "{$originalSlug}-{$count}";
                    $count++;
                }
            }
            // Ensure SKU is unique within the store if provided, or generate one
            if (empty($product->sku) && $product->store_id) {
                // Basic SKU generation: StoreID-CategoryID-Timestamp (or similar logic)
                // $product->sku = 'SKU-' . $product->store_id . '-' . Str::upper(Str::random(6));
            }
        });

        static::updating(function ($product) {
            if ($product->isDirty('name') && empty($product->getOriginal('slug'))) {
                $product->slug = Str::slug($product->name);
                $originalSlug = $product->slug;
                $count = 1;
                $id = $product->id;
                while (static::where('slug', $product->slug)->where('id', '!=', $id)->exists()) {
                    $product->slug = "{$originalSlug}-{$count}";
                    $count++;
                }
            }
        });
    }

    /**
     * Accessor for the primary product image URL.
     */
    public function getPrimaryImageUrlAttribute(): ?string
    {
        // Find the image marked as primary
        $primaryImage = $this->images()->where('is_primary', true)->first();
        if ($primaryImage) {
            return $primaryImage->image_url; // Uses ProductImage model's accessor
        }

        // If no primary image, return the first image's URL (if any)
        $firstImage = $this->images()->orderBy('sort_order')->first();
        if ($firstImage) {
            return $firstImage->image_url;
        }

        // Fallback to a default product image
        return asset('images/default-product.png'); // Add this default image to public/images
    }

    /**
     * Relationship to the Store model.
     * A product belongs to a store.
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }



    /**
     * Relationship to the Category model.
     * A product belongs to a category.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relationship to ProductImage model.
     * A product can have many images.
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order'); // Order images by sort_order
    }

    /**
     * Relationship for Sale Items (products included in sales).
     */
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    // Helper method to update stock
    public function adjustStock(int $quantityChange): bool
    {
        if ($this->stock_quantity + $quantityChange < 0) {
            // Cannot have negative stock
            return false;
        }
        $this->stock_quantity += $quantityChange;
        return $this->save();
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    // Example accessor for total stock across all variants if parent product stock is not primary
    public function getTotalStockAttribute(): int
    {
        if ($this->has_variants ?? $this->variants()->exists()) { // Check if product uses variants
            return $this->variants()->sum('stock_quantity');
        }
        return $this->stock_quantity; // Fallback to parent product's stock
    }

    // Example accessor for price range if variants have different prices
    public function getPriceRangeAttribute(): string
    {
        if ($this->has_variants ?? $this->variants()->exists()) {
            $minPrice = $this->variants()->min(DB::raw('COALESCE(sale_price, product_base_price + additional_price)')); // Requires product_base_price context
            $maxPrice = $this->variants()->max(DB::raw('COALESCE(sale_price, product_base_price + additional_price)'));
            // This DB::raw part is pseudo-code, actual calculation might need joining or more complex accessor logic
            // Simpler:
            $prices = $this->variants->map(fn($variant) => $variant->effective_sale_price);
            if ($prices->isEmpty()) return number_format($this->sale_price, 2); // Fallback
            $min = $prices->min();
            $max = $prices->max();
            return ($min == $max) ? number_format($min, 2) : number_format($min, 2) . " - " . number_format($max, 2);
        }
        return number_format($this->sale_price, 2);
    }
}

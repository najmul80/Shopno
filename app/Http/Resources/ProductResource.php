<?php

namespace App\Http\Resources; // Ensure correct namespace

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'sku' => $this->sku,
            'purchase_price' => (float) $this->purchase_price,
            'sale_price' => (float) $this->sale_price,
            'stock_quantity' => (int) $this->stock_quantity,
            'low_stock_threshold' => (int) $this->low_stock_threshold,
            'unit' => $this->unit,
            'is_active' => (bool) $this->is_active,
            'is_featured' => (bool) $this->is_featured,
            'attributes' => $this->attributes, // JSON decoded to array by model's cast

            'primary_image_url' => $this->primary_image_url, // Accessor from Product model

            // Conditionally load relationships
            'store' => new StoreResource($this->whenLoaded('store')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),

            'has_variants' => $this->has_variants ?? $this->variants()->exists(), // If you added this field
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'total_stock' => $this->total_stock, // Accessor
            'price_range' => $this->price_range, // Accessor

            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}

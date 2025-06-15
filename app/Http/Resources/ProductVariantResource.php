<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'sku' => $this->sku,
            'display_name' => $this->display_name, // Accessor
            'name_suffix' => $this->name_suffix,
            'additional_price' => (float) $this->additional_price,
            'sale_price' => (float) $this->sale_price, // Absolute price if set
            'effective_sale_price' => $this->effective_sale_price, // Accessor
            'purchase_price' => (float) $this->purchase_price,
            'stock_quantity' => (int) $this->stock_quantity,
            'low_stock_threshold' => $this->low_stock_threshold,
            'barcode' => $this->barcode,
            'image_url' => $this->image_url, // Accessor
            'is_active' => (bool) $this->is_active,
            'attributes' => AttributeValueWithAttributeResource::collection($this->whenLoaded('attributeValues')), // New resource
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
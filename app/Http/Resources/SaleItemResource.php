<?php

namespace App\Http\Resources; // Ensure correct namespace

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleItemResource extends JsonResource
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
            // 'sale_id' => $this->sale_id, // Usually not needed if this is part of SaleResource
            'product_id' => $this->product_id,
            'product_name' => $this->whenLoaded('product', $this->product->name, null), // Show product name
            'product_sku' => $this->whenLoaded('product', $this->product->sku, null), // Show product SKU
            // 'product' => new ProductResource($this->whenLoaded('product')), // Optionally, full product details
            'quantity' => (int) $this->quantity,
            'unit_price' => (float) $this->unit_price, // Price at the time of sale
            'item_sub_total' => (float) $this->item_sub_total,
            // 'item_discount' => (float) $this->item_discount, // If you have item-level discount
            // 'item_tax' => (float) $this->item_tax, // If you have item-level tax
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
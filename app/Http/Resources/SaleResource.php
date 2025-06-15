<?php

namespace App\Http\Resources; // Ensure correct namespace

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
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
            'invoice_number' => $this->invoice_number,
            'store' => new StoreResource($this->whenLoaded('store')),
            'user_staff' => new UserResource($this->whenLoaded('user')), // Staff who made the sale
            'customer' => new CustomerResource($this->whenLoaded('customer')),

            'sub_total' => (float) $this->sub_total,
            'discount_amount' => (float) $this->discount_amount,
            'discount_type' => $this->discount_type,
            'tax_percentage' => (float) $this->tax_percentage,
            'tax_amount' => (float) $this->tax_amount,
            'shipping_charge' => (float) $this->shipping_charge,
            'grand_total' => (float) $this->grand_total,

            'amount_paid' => (float) $this->amount_paid,
            'change_returned' => (float) $this->change_returned,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'sale_status' => $this->sale_status,
            'notes' => $this->notes,

            'sale_items' => SaleItemResource::collection($this->whenLoaded('items')), // Collection of sale items

            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
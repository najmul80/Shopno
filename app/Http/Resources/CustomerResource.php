<?php

namespace App\Http\Resources; // Ensure correct namespace

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // $this refers to the Customer model instance
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'address_line1' => $this->address_line1,
            'address_line2' => $this->address_line2,
            'city' => $this->city,
            'state_province' => $this->state_province,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'photo_url' => $this->photo_url, // Accessor from Customer model
            'date_of_birth' => $this->date_of_birth ? $this->date_of_birth->toDateString() : null, // Format date
            'gender' => $this->gender,
            'notes' => $this->notes,
            'is_active' => (bool) $this->is_active,
            'store_id' => $this->store_id,

            // Conditionally load relationships
            'store' => new StoreResource($this->whenLoaded('store')),
            // 'sales_count' => $this->whenCounted('sales'), // Example

            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
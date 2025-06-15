<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
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
            'description' => $this->description,
            'address_line1' => $this->address_line1,
            'address_line2' => $this->address_line2,
            'city' => $this->city,
            'state_province' => $this->state_province,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'website' => $this->website,
            'logo_url' => $this->logo_url, // Uses the accessor from Store model
            'is_active' => (bool) $this->is_active,
            // 'owner_id' => $this->owner_id, // If you have an owner
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            // You can also conditionally load relationships like users related to this store
            // 'users_count' => $this->whenCounted('users'), // Example: if you load user count
            // 'users' => UserResource::collection($this->whenLoaded('users')), // Example: if you load users
        ];
    }
}

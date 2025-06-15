<?php

namespace App\Http\Resources; // Ensure correct namespace

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // The $this keyword here refers to the User model instance being transformed.
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'profile_photo_url' => $this->profile_photo_url, // This will use the accessor we define in the User model
            'email_verified_at' => $this->email_verified_at ? $this->email_verified_at->toDateTimeString() : null,
            'store_id' => $this->store_id, // Will be null initially, populated when store management is added
            'store' => new StoreResource($this->whenLoaded('store')), // Uncomment when StoreResource and relation are ready

            // Conditionally load roles if the 'roles' relationship has been eager-loaded or explicitly loaded
            'roles' => $this->whenLoaded('roles', function () {
                return $this->getRoleNames(); // Spatie's method to get role names as an array
            }, function () {
                // If 'roles' relationship is not loaded, and you still want to attempt to get roles:
                // This might cause an N+1 query problem if used in a collection without eager loading.
                // It's generally better to ensure 'roles' is loaded before calling UserResource.
                // For a single user resource, this is usually fine.
                if ($this->relationLoaded('roles') === false && method_exists($this, 'getRoleNames')) {
                     // Check if roles can be fetched without loading if not already loaded,
                     // however, getRoleNames() usually relies on the relationship being loaded.
                     // For safety, only return if loaded.
                     // return $this->getRoleNames();
                }
                return []; // Return empty array or null if roles are not loaded
            }),
            // 'permissions' => $this->whenLoaded('permissions', function () { // If you also want to return direct permissions
            //     return $this->getPermissionNames();
            // }),
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
        ];
    }
}
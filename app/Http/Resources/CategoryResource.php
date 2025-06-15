<?php

namespace App\Http\Resources; // Ensure correct namespace

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // $this refers to the Category model instance
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_url' => $this->image_url, // Accessor from Category model
            'is_active' => (bool) $this->is_active,
            'sort_order' => (int) $this->sort_order,
            'store_id' => $this->store_id,
            'parent_id' => $this->parent_id,

            // Conditionally load relationships
            'store' => new StoreResource($this->whenLoaded('store')),
            'parent_category' => new CategoryResource($this->whenLoaded('parent')), // Renamed for clarity
            'child_categories' => CategoryResource::collection($this->whenLoaded('children')), // Renamed for clarity

            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
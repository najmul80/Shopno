<?php

namespace App\Http\Requests\Product; // Ensure correct namespace

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by controller middleware
        return true;
    }

    public function rules(): array
    {
        $user = Auth::user();
        $storeIdForValidation = $user->store_id; // Default to user's store

        if ($user->hasRole('super-admin') && $this->input('store_id')) {
            $storeIdForValidation = $this->input('store_id');
        } elseif (!$user->hasRole('super-admin') && !$storeIdForValidation) {
            // Non-super-admin must have a store_id. This will be handled by controller logic before validation.
            // Or we can force a validation error if store_id is not available for them.
        }


        return [
            'name' => 'required|string|max:255',
            'slug' => [
                'nullable', 'string', 'max:255',
                Rule::unique('products', 'slug') // Slug should be globally unique
            ],
            'description' => 'nullable|string',
            'sku' => [
                'nullable', 'string', 'max:100',
                Rule::unique('products', 'sku')->where(function ($query) use ($storeIdForValidation) {
                    return $query->where('store_id', $storeIdForValidation); // SKU unique per store
                })
            ],
            'category_id' => [
                'required', 'integer',
                Rule::exists('categories', 'id')->where(function ($query) use ($storeIdForValidation) {
                    return $query->where('store_id', $storeIdForValidation); // Category must belong to the same store
                })
            ],
            'purchase_price' => 'nullable|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'unit' => 'nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
            'attributes' => 'nullable|json', // Validate as JSON string
            'store_id' => [ // Only applicable if super-admin is submitting
                Rule::requiredIf(fn () => $user->hasRole('super-admin')),
                'integer',
                'exists:stores,id'
            ],
            // For initial images during product creation
            'images' => 'nullable|array|max:5', // Allow up to 5 images initially
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048', // Each image validation
            'primary_image_index' => 'nullable|integer|min:0', // Index of the image in 'images' array to be primary
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.exists' => 'The selected category does not exist or does not belong to the specified store.',
            'images.max' => 'You can upload a maximum of 5 images at a time.',
            'images.*.image' => 'One of the uploaded files is not a valid image.',
            'images.*.mimes' => 'Images must be of type: jpeg, png, jpg, gif.',
            'images.*.max' => 'Each image may not be greater than 2MB.',
            'primary_image_index.integer' => 'Primary image selection is invalid.'
        ];
    }
}
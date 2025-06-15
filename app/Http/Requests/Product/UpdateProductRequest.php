<?php

namespace App\Http\Requests\Product; // Ensure correct namespace

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by controller middleware
    }

    public function rules(): array
    {
        $product = $this->route('product'); // Get the product model instance from route model binding
        $productId = $product->id;
        $storeIdForValidation = $product->store_id; // Product's current store_id

        return [
            'name' => 'sometimes|required|string|max:255',
            'slug' => [
                'sometimes', 'nullable', 'string', 'max:255',
                Rule::unique('products', 'slug')->ignore($productId)
            ],
            'description' => 'nullable|string',
            'sku' => [
                'sometimes', 'nullable', 'string', 'max:100',
                Rule::unique('products', 'sku')->where(function ($query) use ($storeIdForValidation) {
                    return $query->where('store_id', $storeIdForValidation);
                })->ignore($productId)
            ],
            'category_id' => [
                'sometimes', 'required', 'integer',
                Rule::exists('categories', 'id')->where(function ($query) use ($storeIdForValidation) {
                    return $query->where('store_id', $storeIdForValidation);
                })
            ],
            'purchase_price' => 'nullable|numeric|min:0',
            'sale_price' => 'sometimes|required|numeric|min:0',
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'unit' => 'nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
            'attributes' => 'nullable|json',
            // store_id is typically not changed during product update, unless by super-admin with specific logic
        ];
    }
     public function messages(): array
    {
        return [
            'category_id.exists' => 'The selected category does not exist or does not belong to this product\'s store.',
        ];
    }
}
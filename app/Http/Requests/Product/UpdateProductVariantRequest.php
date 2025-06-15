<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductVariantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $product = $this->route('product'); // Parent product
        return [
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('product_variants', 'sku')->where(function ($query) use ($product) {
                    // A variant SKU could be globally unique, or unique within a product, or unique within a store
                    // For simplicity, let's make it unique for this product for now.
                    // If globally unique, just Rule::unique('product_variants', 'sku')
                    return $query->where('product_id', $product->id);
                })
            ],
            'name_suffix' => 'nullable|string|max:255',
            'additional_price' => 'nullable|numeric',
            'sale_price' => 'nullable|numeric|min:0', // Absolute price for variant
            'purchase_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'barcode' => 'nullable|string|max:255|unique:product_variants,barcode',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Specific image for this variant
            'is_active' => 'sometimes|boolean',
            'attribute_value_ids' => 'required|array|min:1', // At least one attribute combination
            'attribute_value_ids.*' => 'required|integer|exists:attribute_values,id',
            'barcode' => [
                'nullable',
                'string',
                'max:255', // Adjust as needed
                Rule::unique('product_variants', 'barcode')->ignore($this->variant ? $this->variant->id : null)->withoutTrashed(),
                // You might add specific barcode format validation if needed (e.g., EAN-13, UPC-A)
                // 'regex:/^[0-9]{12,13}$/' // Example for EAN-13 or UPC-A like structure
            ],
        ];
    }
}

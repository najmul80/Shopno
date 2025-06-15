<?php

namespace App\Http\Requests\Sale; // Ensure correct namespace

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule; // For Rule-based validation

class StoreSaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Authorization is handled by controller middleware ('permission:process sales').
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = Auth::user();
        $storeIdForProductExistence = $user->store_id;

        // If super admin is making the sale and provides a store_id, use that for product existence check
        if ($user->hasRole('super-admin') && $this->input('store_id')) {
            $storeIdForProductExistence = $this->input('store_id');
        }

        return [
            'store_id' => [ // Applicable and potentially required if super-admin is creating a sale
                Rule::requiredIf(fn () => $user->hasRole('super-admin')),
                'nullable', // Can be null if SA is creating for a non-store-specific context (if applicable) or if store_id is derived differently
                'integer',
                'exists:stores,id'
            ],
            'customer_id' => [
                'nullable',
                'integer',
                // Customer must exist and belong to the same store as the sale (if store context is determined)
                Rule::exists('customers', 'id')->where(function ($query) use ($storeIdForProductExistence) {
                    if ($storeIdForProductExistence) { // Apply store scope only if store_id is determined
                        return $query->where('store_id', $storeIdForProductExistence);
                    }
                    return $query; // No store scope if store_id is not determined (e.g., global customer)
                }),
            ],
            'items' => 'required|array|min:1', // At least one item is required for a sale
            'items.*.product_id' => [ // For each item in the 'items' array
                'required',
                'integer',
                // Product must exist and belong to the determined store for the sale
                Rule::exists('products', 'id')->where(function ($query) use ($storeIdForProductExistence) {
                    if ($storeIdForProductExistence) {
                        return $query->where('store_id', $storeIdForProductExistence);
                    }
                    // If storeId is not determined yet (e.g. super-admin without store_id in request),
                    // this rule might need adjustment or be handled in service layer.
                    // For now, it assumes product must exist globally if no store context.
                    return $query;
                }),
            ],
            'items.*.quantity' => 'required|integer|min:1', // Quantity must be at least 1

            // Financials (optional, can be calculated by backend or provided)
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|string|in:percentage,fixed', // If you support percentage discounts
            'tax_percentage' => 'nullable|numeric|min:0|max:100', // Tax rate as percentage
            'shipping_charge' => 'nullable|numeric|min:0',
            'amount_paid' => 'nullable|numeric|min:0', // Amount paid by customer

            // Sale Status & Payment
            'payment_method' => 'nullable|string|in:cash,card,mobile_banking,online,cheque,bank_transfer',
            'payment_status' => 'nullable|string|in:pending,paid,partially_paid,failed,refunded',
            'sale_status' => 'nullable|string|in:pending,processing,completed,shipped,delivered,cancelled,returned',
            'notes' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one product item is required to process a sale.',
            'items.min' => 'At least one product item must be added to the sale.',
            'items.*.product_id.required' => 'Product ID is required for each item.',
            'items.*.product_id.exists' => 'The selected product does not exist or does not belong to the specified store.',
            'items.*.quantity.required' => 'Quantity is required for each item.',
            'items.*.quantity.min' => 'Quantity for each item must be at least 1.',
            'store_id.required_if' => 'As a super admin, you must specify a store ID if creating a sale for a specific store.',
            'customer_id.exists' => 'The selected customer does not exist or does not belong to the specified store.',
        ];
    }
}
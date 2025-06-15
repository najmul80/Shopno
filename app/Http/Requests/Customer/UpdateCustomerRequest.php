<?php

namespace App\Http\Requests\Customer; // Ensure correct namespace

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by controller middleware.
        // Can add specific logic: user can only update customers of their own store.
        // $customer = $this->route('customer');
        // $user = Auth::user();
        // return $user->hasRole('super-admin') || ($user->store_id === $customer->store_id && $user->can('update own_store_customers'));
        return true;
    }

    public function rules(): array
    {
        $customer = $this->route('customer'); // Get the customer model instance
        $customerId = $customer->id;
        $storeIdForValidation = $customer->store_id;

        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes', 'nullable', 'string', 'email', 'max:255',
                Rule::unique('customers', 'email')->ignore($customerId)
                    // If email unique per store:
                    // Rule::unique('customers')->where(function ($query) use ($storeIdForValidation) {
                    //     return $query->where('store_id', $storeIdForValidation);
                    // })->ignore($customerId)
            ],
            'phone_number' => [
                'sometimes', 'nullable', 'string', 'max:20',
                Rule::unique('customers', 'phone_number')->ignore($customerId)
                    // If phone unique per store:
                    // Rule::unique('customers')->where(function ($query) use ($storeIdForValidation) {
                    //     return $query->where('store_id', $storeIdForValidation);
                    // })->ignore($customerId)
            ],
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state_province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'date_of_birth' => 'nullable|date_format:Y-m-d|before_or_equal:today',
            'gender' => ['nullable', 'string', Rule::in(['male', 'female', 'other'])],
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
            // store_id is generally not updatable for a customer directly by store-admin. Super-admin might.
        ];
    }
}
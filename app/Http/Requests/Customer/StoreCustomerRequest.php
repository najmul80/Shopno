<?php

namespace App\Http\Requests\Customer; // Ensure correct namespace

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by controller middleware
        return true;
    }

    public function rules(): array
    {
        $user = Auth::user();
        $storeIdForValidation = $user->store_id;

        if ($user->hasRole('super-admin') && $this->input('store_id')) {
            $storeIdForValidation = $this->input('store_id');
        } elseif (!$user->hasRole('super-admin') && !$storeIdForValidation) {
            // This case should be handled in controller, non-super-admin must have a store
        }

        return [
            'name' => 'required|string|max:255',
            'email' => [
                'nullable', 'string', 'email', 'max:255',
                Rule::unique('customers', 'email') // Globally unique email for customers
                    // If email should be unique per store:
                    // Rule::unique('customers')->where(function ($query) use ($storeIdForValidation) {
                    //     return $query->where('store_id', $storeIdForValidation);
                    // })
            ],
            'phone_number' => [
                'nullable', 'string', 'max:20',
                Rule::unique('customers', 'phone_number') // Globally unique phone for customers
                    // If phone should be unique per store:
                    // Rule::unique('customers')->where(function ($query) use ($storeIdForValidation) {
                    //     return $query->where('store_id', $storeIdForValidation);
                    // })
            ],
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state_province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Customer photo
            'date_of_birth' => 'nullable|date_format:Y-m-d|before_or_equal:today',
            'gender' => ['nullable', 'string', Rule::in(['male', 'female', 'other'])],
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
            'store_id' => [ // Only applicable if super-admin is submitting
                Rule::requiredIf(fn () => $user->hasRole('super-admin')),
                'integer',
                'exists:stores,id'
            ],
        ];
    }
     public function messages(): array
    {
        return [
            'store_id.required_if' => 'As a super admin, you must specify a store ID for the customer.',
            'email.unique' => 'This email address is already registered for another customer.',
            'phone_number.unique' => 'This phone number is already registered for another customer.',
        ];
    }
}
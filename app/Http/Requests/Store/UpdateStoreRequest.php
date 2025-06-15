<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule; 
class UpdateStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
        $storeId = $this->route('store')->id; // Get the ID of the store being updated from the route parameter

        return [
            'name' => [
                'sometimes', // Validate only if present in the request
                'required',
                'string',
                'max:255',
                Rule::unique('stores', 'name')->ignore($storeId), // Name must be unique, ignoring the current store
            ],
            'description' => 'nullable|string|max:1000',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state_province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'phone_number' => 'nullable|string|max:20',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('stores', 'email')->ignore($storeId), // Email must be unique, ignoring current store
            ],
            'website' => 'nullable|url|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // New logo image file
            'is_active' => 'sometimes|boolean',
        ];
    }
}

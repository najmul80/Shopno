<?php

namespace App\Http\Requests\Auth; // Ensure correct namespace

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password; // For using strong password validation rules

class RegisterUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * In this case, anyone can attempt to register.
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
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email', // Email must be unique in the 'users' table
            'password' => [
                'required',
                'string',
                Password::min(8) // Password must be at least 8 characters
                    ->letters() // Must contain at least one letter
                    ->mixedCase() // Must contain at least one uppercase and one lowercase letter
                    ->numbers() // Must contain at least one number
                    ->symbols() // Must contain at least one symbol (e.g., ! @ # $)
                    ->uncompromised(), // Checks if the password has appeared in a data breach via haveibeenpwned.com API
                'confirmed' // Requires a 'password_confirmation' field in the request that matches 'password'
            ],
            'is_active' => 'sometimes|boolean',
            'terms' => 'required|accepted',
            // We will add 'store_name' or 'store_id' rules later if registration involves creating/joining a store.
            // For now, these are the basic user registration fields.
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'terms.required' => 'You must agree to the terms of use.',
            'terms.accepted' => 'You must accept the terms of use to register.',
            // You can add more custom messages for Password rule specifics if needed
        ];
    }
}

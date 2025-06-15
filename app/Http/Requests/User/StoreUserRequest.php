<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Authorization will be handled by controller middleware.
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
        $user = Auth::user(); // The user performing the action (creating another user)
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => [
                'required',
                'string',
                Password::min(8) // Password must be at least 8 characters
                        ->letters() // Must contain at least one letter
                        ->mixedCase() // Must contain at least one uppercase and one lowercase letter
                        ->numbers() // Must contain at least one number
                        ->symbols() // Must contain at least one symbol
                        ->uncompromised(), // Optionally check if password has been pwned (requires internet)
                'confirmed' // Requires a 'password_confirmation' field in the request
            ],
            'roles' => 'required|array|min:1', // User must be assigned at least one role
            'roles.*' => [ // Each role in the 'roles' array
                'string',
                Rule::exists('roles', 'name')->where(function ($query) {
                    // Optionally, you can scope the role existence check to a specific guard
                    // if you have multiple authentication guards using roles (e.g., 'web', 'api').
                    return $query->where('guard_name', 'api');
                    // For now, assuming roles are globally unique by name for the default guard used by Spatie.
                }),
            ],
            'store_id' => [
                // store_id is required if the acting user is a super-admin AND
                // the roles being assigned to the new user DO NOT include 'super-admin'.
                // This means a super-admin must assign a store to non-super-admin users they create.
                Rule::requiredIf(function () use ($user) {
                    // Check if the acting user is a super-admin
                    $isActingUserSuperAdmin = $user && method_exists($user, 'hasRole') && $user->hasRole('super-admin');
                    // Check if the 'super-admin' role is NOT among the roles being assigned to the new user
                    $isAssigningSuperAdminRole = in_array('super-admin', $this->input('roles', []));

                    return $isActingUserSuperAdmin && !$isAssigningSuperAdminRole;
                }),
                'nullable', // A super-admin user themselves might not have a store_id
                'integer',
                'exists:stores,id' // If provided, store_id must exist in the 'stores' table
            ],
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // Max 2MB, common image types + webp
            'is_active' => 'sometimes|boolean', // Optional: for user status (if your User model has this)
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array // Crucially, this method MUST return an array
    {
        return [
            'name.required' => 'The user name is required.',
            'name.string' => 'The user name must be a string.',
            'name.max' => 'The user name may not be greater than 255 characters.',

            'email.required' => 'The email address is required.',
            'email.string' => 'The email address must be a string.',
            'email.email' => 'Please provide a valid email address.',
            'email.max' => 'The email address may not be greater than 255 characters.',
            'email.unique' => 'This email address has already been registered.',

            'password.required' => 'The password is required.',
            'password.string' => 'The password must be a string.',
            'password.confirmed' => 'The password confirmation does not match.',
            // Custom messages for Password rule object can be more complex or rely on Laravel's defaults.
            // Example for min length if Password rule object wasn't used:
            // 'password.min' => 'The password must be at least 8 characters.',

            'roles.required' => 'At least one role must be assigned to the user.',
            'roles.array' => 'The roles must be provided as an array.',
            'roles.min' => 'At least one role must be selected.',
            'roles.*.string' => 'Each role must be a string (role name).',
            'roles.*.exists' => 'One or more of the selected roles is invalid or does not exist.',

            'store_id.required_if' => 'A store assignment is required for this user and role combination.',
            'store_id.integer' => 'The store ID must be an integer.',
            'store_id.exists' => 'The selected store does not exist.',

            'profile_photo.image' => 'The profile photo must be an image file (jpeg, png, jpg, gif, webp).',
            'profile_photo.mimes' => 'The profile photo must be a file of type: :values.',
            'profile_photo.max' => 'The profile photo may not be greater than 2MB in size.',

            'is_active.boolean' => 'The active status must be true or false.',
        ];
       
    }
}
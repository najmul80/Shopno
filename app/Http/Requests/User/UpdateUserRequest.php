<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
// User মডেল ইম্পোর্ট করার দরকার নেই rules() মেথডের ভিতরে সরাসরি ব্যবহারের জন্য,
// যদি না আপনি instanceof বা স্ট্যাটিক মেথড কল করেন।

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is primarily handled by route/controller middleware
        return true;
    }

    public function rules(): array
    {
        // Get the user ID from the route parameter.
        // When the controller signature is `update(UpdateUserRequest $request, $userId)`,
        // $this->route('user') will give the value of the {user} segment from the URL.
        $userIdToUpdate = $this->route('user'); // This should be the ID of the user being updated.

        // Log for debugging to confirm what $userIdToUpdate is
        \Illuminate\Support\Facades\Log::info('UpdateUserRequest - ID of user to update from route: ' . $userIdToUpdate);

        $currentUser = Auth::user(); // The user performing the update action

        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                // Use the scalar ID for ignoring in unique rule
                Rule::unique('users', 'email')->ignore($userIdToUpdate), // $userIdToUpdate is now the ID
            ],
            'password' => [
                'nullable', // Password is optional on update
                'string',
                Password::min(8)->letters()->mixedCase()->numbers()->symbols(), // Apply strong password rules if provided
                'confirmed' // Requires password_confirmation field if password is present
            ],
            'roles' => 'sometimes|array|min:1', // 'sometimes' because roles might not be updated every time
            'roles.*' => [
                'string',
                Rule::exists('roles', 'name')->where(function ($query) {
                    return $query->where('guard_name', 'api'); // Good practice to scope to guard
                })
            ],
            'store_id' => [
                // This rule is complex and depends on who is updating whom.
                // Simplified: Only super-admin can change store_id.
                // More complex logic (like in your controller) is hard to put directly in requiredIf here
                // without fetching the user model being updated.
                // For FormRequest, you might make it 'nullable' and let controller handle conditional requirement.
                Rule::requiredIf(function () use ($currentUser) {
                    // Example: If current user is SA and they are trying to assign a non-SA role, store_id might be required.
                    // This is tricky. Let's make it nullable and handle specific logic in controller for SA.
                    return $currentUser && $currentUser->hasRole('super-admin') &&
                           (isset($this->roles) && !in_array('super-admin', $this->input('roles', [])));
                }),
                'nullable',
                'integer',
                'exists:stores,id'
            ],
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email address is already in use by another user.',
            'roles.*.exists' => 'One or more selected roles are invalid for the API guard.',
            'store_id.required_if' => 'A store assignment is required for this user/role combination if you are a Super Admin.',
            // Add other custom messages as needed
        ];
    }
}
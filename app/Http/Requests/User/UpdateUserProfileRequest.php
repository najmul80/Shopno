<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * The 'auth:api' middleware on the route will ensure the user is authenticated.
     * Further specific authorization (e.g., can only update own profile) can be done here or in policy.
     */
    public function authorize(): bool
    {
        // For now, if the user is authenticated, they can attempt to update a profile.
        // Specific checks (e.g., updating own profile vs admin updating other's) would go in controller/policy.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Get the ID of the authenticated user, who is making the request
        $userId = $this->user()->id;

        return [
            'name' => 'sometimes|required|string|max:255', // 'sometimes' means validate only if present
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId), // Email must be unique, ignoring the current user's record
            ],
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB, common image types
            // Add rules for other updatable profile fields if any (e.g., phone_number, bio)
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
            'profile_photo.image' => 'The profile photo must be an image file.',
            'profile_photo.mimes' => 'The profile photo must be a file of type: jpeg, png, jpg, gif.',
            'profile_photo.max' => 'The profile photo may not be greater than 2MB.',
        ];
    }
}

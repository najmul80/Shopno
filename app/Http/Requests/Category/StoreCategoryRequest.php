<?php

namespace App\Http\Requests\Category; // Ensure correct namespace

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth; // To get authenticated user
use Illuminate\Validation\Rule; // For more complex validation rules

class StoreCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Authorization will be handled by controller middleware based on permissions.
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
        // Get the store_id of the authenticated user.
        // A category must belong to the store of the user creating it.
        // Super admin might be able to specify a store_id.
        $user = Auth::user();
        $storeId = $user->store_id;

        if ($user->hasRole('super-admin') && $this->input('store_id')) {
            // If super-admin is creating and specifies a store_id, use that
            $storeIdForValidation = $this->input('store_id');
        } elseif (!$user->hasRole('super-admin') && $storeId) {
            // If not super-admin, use their own store_id
            $storeIdForValidation = $storeId;
        } else if ($user->hasRole('super-admin') && !$this->input('store_id')) {
            // Super admin must provide a store_id if not creating for a general pool (if applicable)
            // Or handle this case based on your app's logic (e.g., global categories)
            // For now, let's make store_id required for super-admin if they don't have one by default.
            // This rule structure assumes a store_id will always be determined.
            // Better to handle the store_id logic for assignment in the controller or a service.
             return [
                // This is a fallback, main logic is in controller/service for store_id assignment.
                'name' => 'required|string|max:255',
                'store_id_for_super_admin' => 'required_if_input_does_not_have_store_id_and_user_is_super_admin_without_default_store|exists:stores,id', // Placeholder
                 // ... other rules
            ];
        }
         else if (!$storeId) {
            // If a non-super-admin user does not have a store_id, they cannot create a store-specific category.
            // This scenario should ideally be prevented by UI or earlier checks.
            // We can add a custom validation rule or throw an exception here if needed.
            // For FormRequest, just define rules. Controller will handle this state.
             return [
                'user_must_have_store' => 'required', // This will fail if storeId is null
                 // ... other rules
            ];
        }


        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // Rule to make category name unique within the same store and under the same parent_id
                // This can get complex with nullable parent_id.
                // For simplicity, slug uniqueness is already handled by the model.
                // We can add a unique check for (name, store_id, parent_id) if strictly needed.
                // Example: Rule::unique('categories')->where(function ($query) use ($storeIdForValidation) {
                //     return $query->where('store_id', $storeIdForValidation)
                //                  ->where('parent_id', $this->input('parent_id'));
                // }),
            ],
            'slug' => [ // Slug is auto-generated, but can be provided optionally
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->where(function ($query) use ($storeIdForValidation) {
                    // Slug should ideally be unique globally or at least per store.
                    // Global uniqueness is simpler for URLs.
                    return $query; // No extra condition for store_id on slug if globally unique
                }),
            ],
            'description' => 'nullable|string|max:1000',
            'parent_id' => [
                'nullable',
                'integer',
                // Parent category must exist and belong to the same store as the child
                Rule::exists('categories', 'id')->where(function ($query) use ($storeIdForValidation) {
                    return $query->where('store_id', $storeIdForValidation);
                }),
            ],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Category image file
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0',
            'store_id' => [ // This field might be sent by super-admin
                Rule::requiredIf(fn () => $user->hasRole('super-admin')),
                'integer',
                'exists:stores,id'
            ]
        ];
    }
    public function messages(): array
    {
        return [
            'store_id.required_if' => 'As a super admin, you must specify a store ID.',
            'parent_id.exists' => 'The selected parent category does not exist or does not belong to the same store.',
        ];
    }
}
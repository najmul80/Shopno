<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class AddProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by controller middleware (e.g., can update product)
        return true;
    }

    public function rules(): array
    {
        return [
            'images' => 'required|array|min:1|max:5', // Require at least one image, max 5 at a time
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB per image
            // 'is_primary_flags' => 'nullable|array', // Optional: array of booleans to mark an image as primary
            // 'is_primary_flags.*' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'images.required' => 'Please upload at least one image.',
            'images.max' => 'You can upload a maximum of 5 images at a time.',
            'images.*.required' => 'An image file is required.',
            'images.*.image' => 'One of the uploaded files is not a valid image.',
            'images.*.mimes' => 'Images must be of type: jpeg, png, jpg, gif.',
            'images.*.max' => 'Each image may not be greater than 2MB.',
        ];
    }
}
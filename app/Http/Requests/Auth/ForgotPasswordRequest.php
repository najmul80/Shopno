<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|string|email|exists:users,email', // Ensure the email exists in the 'users' table
        ];
    }

    public function messages(): array
    {
        return [
            'email.exists' => 'This email address is not registered with us. Please check and try again.',
        ];
    }
}

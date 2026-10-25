<?php

namespace App\Http\Requests\Auth;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    public function rules(): array
    {
        return [
            'full_name'             => ['required', 'string', 'max:150'],
            'phone'                 => ['required', 'string', 'max:20', 'unique:users,phone'],
            'email'                 => ['nullable', 'email', 'max:150', 'unique:users,email'],
            'aadhar_number'         => ['nullable', 'string', 'max:25'],
            'pan_number'            => ['nullable', 'string', 'max:20'],
            'password'              => ['required', 'confirmed', Password::min(6)],
            'password_confirmation' => ['required'],
            'role'                  => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'role.in' => 'The role must be WORKER or OFFICER. ADMIN accounts cannot be self-registered.',
        ];
    }
}

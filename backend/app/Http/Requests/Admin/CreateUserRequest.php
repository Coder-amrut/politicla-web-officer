<?php

namespace App\Http\Requests\Admin;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by EnsureAdmin middleware
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:150'],
            'phone'     => ['required', 'string', 'max:20', 'unique:users,phone'],
            'email'     => ['nullable', 'email', 'max:150', 'unique:users,email'],
            'password'  => ['required', Password::min(8)->mixedCase()->numbers()],
            'role'      => ['required', 'string', 'in:' . implode(',', RoleName::publicRegistrable())],
        ];
    }

    public function messages(): array
    {
        return [
            'role.in' => 'Admin-created accounts must be WORKER or OFFICER. ADMIN cannot be created through this endpoint.',
        ];
    }
}

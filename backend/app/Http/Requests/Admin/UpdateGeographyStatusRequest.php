<?php

namespace App\Http\Requests\Admin;

use App\Enums\GeographyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateGeographyStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by EnsureAdmin middleware
    }

    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(GeographyStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status field is required.',
            'status.enum'     => 'Status must be either ACTIVE or INACTIVE.',
        ];
    }
}

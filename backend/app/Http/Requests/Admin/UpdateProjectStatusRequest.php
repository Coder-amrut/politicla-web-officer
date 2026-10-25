<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateProjectStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by EnsureAdmin middleware
    }

    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(ProjectStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'A target status is required.',
            'status.enum'     => 'Status must be one of: DRAFT, ACTIVE, COMPLETED, ARCHIVED.',
        ];
    }
}

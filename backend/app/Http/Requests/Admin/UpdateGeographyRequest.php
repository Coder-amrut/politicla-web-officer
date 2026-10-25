<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGeographyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by EnsureAdmin middleware
    }

    public function rules(): array
    {
        return [
            'name'        => ['sometimes', 'required', 'string', 'max:200'],
            'parent_uuid' => ['nullable', 'string', 'uuid', 'exists:geographies,uuid'],
            'code'        => ['nullable', 'string', 'max:100'],
            'latitude'    => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'   => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'Geography name cannot be empty.',
            'parent_uuid.exists' => 'Parent geography does not exist.',
            'latitude.between'   => 'Latitude must be between -90 and 90 degrees.',
            'longitude.between'  => 'Longitude must be between -180 and 180 degrees.',
        ];
    }
}

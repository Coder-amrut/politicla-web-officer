<?php

namespace App\Http\Requests\Admin;

use App\Enums\GeographyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreGeographyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by EnsureAdmin middleware
    }

    public function rules(): array
    {
        return [
            'geography_type_id' => ['required', 'integer', 'exists:geography_types,id'],
            'parent_uuid'       => ['nullable', 'string', 'uuid', 'exists:geographies,uuid'],
            'name'              => ['required', 'string', 'max:200'],
            'code'              => ['nullable', 'string', 'max:100'],
            'latitude'          => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'         => ['nullable', 'numeric', 'between:-180,180'],
            'status'            => ['nullable', new Enum(GeographyStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'geography_type_id.required' => 'Geography type is required.',
            'geography_type_id.exists'   => 'Selected geography type does not exist.',
            'parent_uuid.exists'         => 'Parent geography does not exist.',
            'name.required'              => 'Geography name is required.',
            'latitude.between'           => 'Latitude must be between -90 and 90 degrees.',
            'longitude.between'          => 'Longitude must be between -180 and 180 degrees.',
            'status.enum'                => 'Status must be either ACTIVE or INACTIVE.',
        ];
    }
}

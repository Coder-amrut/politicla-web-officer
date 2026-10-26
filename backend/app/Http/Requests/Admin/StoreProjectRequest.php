<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by EnsureAdmin middleware
    }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:200'],
            'project_type' => ['required', 'string', 'max:100'],
            'description'  => ['nullable', 'string'],
            'status'       => ['nullable', new Enum(ProjectStatus::class)],
            'start_date'   => ['nullable', 'date'],
            'end_date'     => ['nullable', 'date', 'after_or_equal:start_date'],
            'geographic_scope' => ['sometimes', 'nullable', 'string', 'in:DISTRICT,LOK_SABHA,ASSEMBLY,POLLING_STATION,GRADUATE_CONSTITUENCY,TEACHER_CONSTITUENCY,LOCAL_AUTHORITIES,MLA_QUOTA,PRESIDENTIAL,VICE_PRESIDENTIAL,RAJYA_SABHA,MUNICIPAL_CORPORATION,MUNICIPAL_COUNCIL,ZILLA_PARISHAD,PANCHAYAT_SAMITI,GRAM_PANCHAYAT,GENERAL'],
            'geographic_code' => ['nullable', 'string', 'max:5000'],
            'geographic_name_en' => ['nullable', 'string', 'max:5000'],
            'geographic_name_mr' => ['nullable', 'string', 'max:5000'],
            'geographic_context' => ['nullable', 'array'],
            'geographic_context.district_code' => ['nullable'],
            'geographic_context.district_codes' => ['nullable', 'array'],
            'geographic_context.district_codes.*' => ['nullable'],
            'geographic_context.assembly_no' => ['nullable'],
            'geographic_context.election_year' => ['nullable', 'integer', 'min:1900', 'max:2200'],
            'geographic_context.assembly_codes' => ['nullable', 'array'],
            'geographic_context.assembly_codes.*' => ['nullable'],
            'geographic_context.taluka_codes' => ['nullable', 'array'],
            'geographic_context.taluka_codes.*' => ['nullable'],
            'geographic_context.village_codes' => ['nullable', 'array'],
            'geographic_context.village_codes.*' => ['nullable'],
            'geographic_context.polling_stations' => ['nullable', 'array'],
            'geographic_context.polling_stations.*.code' => ['nullable'],
            'geographic_context.polling_stations.*.name' => ['nullable', 'string', 'max:500'],
            'geographic_context.polling_stations.*.name_en' => ['nullable', 'string', 'max:500'],
            'geographic_context.polling_stations.*.local_body_type' => ['nullable', 'string', 'max:100'],
            'geographic_context.polling_stations.*.type_label' => ['nullable', 'string', 'max:150'],
            'geographic_context.polling_stations.*.taluka_code' => ['nullable'],
            'geographic_context.polling_stations.*.district_code' => ['nullable'],
            'geographic_context.polling_stations.*.part_no' => ['nullable'],
            'geographic_context.polling_stations.*.election_year' => ['nullable', 'integer', 'min:1900', 'max:2200'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.enum'              => 'Status must be one of: DRAFT, ACTIVE, COMPLETED, ARCHIVED.',
            'end_date.after_or_equal'  => 'End date must be on or after the start date.',
        ];
    }
}

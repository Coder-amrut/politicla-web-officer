<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyForm extends Model
{
    protected $table = 'survey_form';

    protected $guarded = [];

    protected $casts = [
        'specialized_title' => 'array',
        'modules' => 'array',
        'specialized_questions' => 'array',
        'custom_questions' => 'array',
        'issues' => 'array',
        'communication_channels' => 'array',
        'form_data' => 'array',
    ];
}

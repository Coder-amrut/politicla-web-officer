<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SurveyForm;
use Database\Seeders\SurveyFormSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SurveyFormController extends Controller
{
    /**
     * GET /api/admin/survey-forms/templates
     * Fetch all scope form builder configurations from MySQL table `survey_form`.
     */
    public function getTemplates(): JsonResponse
    {
        $existing = SurveyForm::where('record_type', 'TEMPLATE')->get();

        // If table doesn't have the 6 standard scopes seeded, auto-seed from SurveyFormSeeder
        if ($existing->count() < 6) {
            (new SurveyFormSeeder())->run();
            $existing = SurveyForm::where('record_type', 'TEMPLATE')->get();
        }

        $templates = [];
        foreach ($existing as $row) {
            // Prefer form_data JSON if available, otherwise reconstruct from columns
            $data = $row->form_data;
            if (! is_array($data)) {
                $data = [
                    'formTitle' => ['en' => $row->title_en, 'mr' => $row->title_mr],
                    'campaignObjective' => $row->campaign_objective,
                    'targetAudience' => $row->target_audience,
                    'specializedTitle' => $row->specialized_title ?: ['en' => $row->title_en, 'mr' => $row->title_mr],
                    'modules' => $row->modules ?: [],
                    'specializedQuestions' => $row->specialized_questions ?: [],
                    'customQuestions' => $row->custom_questions ?: [],
                    'issues' => $row->issues ?: [],
                    'communicationChannels' => $row->communication_channels ?: [],
                ];
            } else {
                // Ensure customQuestions exists
                if (! isset($data['customQuestions'])) {
                    $data['customQuestions'] = $row->custom_questions ?: [];
                }
            }

            $templates[$row->scope] = $data;
        }

        return response()->json([
            'success' => true,
            'source' => 'MySQL database table: survey_form',
            'data' => $templates,
        ]);
    }

    /**
     * PUT/POST /api/admin/survey-forms/templates/{scope}
     * Persist updated scope template & custom questions into MySQL table `survey_form`.
     */
    public function saveTemplate(Request $request, string $scope): JsonResponse
    {
        $payload = $request->all();

        $formTitleEn = $payload['formTitle']['en'] ?? ($payload['formTitle'] ?? $scope . ' Survey');
        $formTitleMr = $payload['formTitle']['mr'] ?? ($payload['formTitle'] ?? $scope . ' पाहणी');

        $record = SurveyForm::updateOrCreate(
            [
                'scope' => strtoupper($scope),
                'record_type' => 'TEMPLATE',
            ],
            [
                'form_code' => 'TPL_' . strtoupper($scope),
                'title_en' => is_string($formTitleEn) ? $formTitleEn : json_encode($formTitleEn),
                'title_mr' => is_string($formTitleMr) ? $formTitleMr : json_encode($formTitleMr),
                'campaign_objective' => $payload['campaignObjective'] ?? null,
                'target_audience' => $payload['targetAudience'] ?? null,
                'specialized_title' => $payload['specializedTitle'] ?? null,
                'modules' => $payload['modules'] ?? [],
                'specialized_questions' => $payload['specializedQuestions'] ?? [],
                'custom_questions' => $payload['customQuestions'] ?? [],
                'issues' => $payload['issues'] ?? [],
                'communication_channels' => $payload['communicationChannels'] ?? [],
                'form_data' => $payload,
                'status' => 'ACTIVE',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Form Builder template for ' . $scope . ' successfully saved to table `survey_form`',
            'source' => 'MySQL database table: survey_form',
            'record_id' => $record->id,
            'data' => $record->form_data,
        ]);
    }

    /**
     * POST /api/admin/survey-forms/templates/{scope}/reset
     * Reset a scope template back to system default in MySQL table `survey_form`.
     */
    public function resetTemplate(string $scope): JsonResponse
    {
        $scopeUpper = strtoupper($scope);
        $defaults = SurveyFormSeeder::getDefaultTemplates();

        if (! isset($defaults[$scopeUpper])) {
            return response()->json([
                'success' => false,
                'message' => 'Scope ' . $scope . ' not found in default catalog.',
            ], 404);
        }

        $defaultTpl = $defaults[$scopeUpper];

        $record = SurveyForm::updateOrCreate(
            [
                'scope' => $scopeUpper,
                'record_type' => 'TEMPLATE',
            ],
            [
                'form_code' => 'TPL_' . $scopeUpper,
                'title_en' => $defaultTpl['formTitle']['en'],
                'title_mr' => $defaultTpl['formTitle']['mr'],
                'campaign_objective' => $defaultTpl['campaignObjective'] ?? null,
                'target_audience' => $defaultTpl['targetAudience'] ?? null,
                'specialized_title' => $defaultTpl['specializedTitle'] ?? null,
                'modules' => $defaultTpl['modules'] ?? [],
                'specialized_questions' => $defaultTpl['specializedQuestions'] ?? [],
                'custom_questions' => [],
                'issues' => $defaultTpl['issues'] ?? [],
                'communication_channels' => [],
                'form_data' => $defaultTpl,
                'status' => 'ACTIVE',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Reset template ' . $scopeUpper . ' to default in table `survey_form`',
            'source' => 'MySQL database table: survey_form',
            'data' => $record->form_data,
        ]);
    }

    /**
     * GET /api/admin/survey-forms/drafts
     * Fetch saved survey drafts from MySQL table `survey_form`.
     */
    public function getDrafts(): JsonResponse
    {
        $rows = SurveyForm::where('record_type', 'DRAFT')
            ->orderBy('updated_at', 'desc')
            ->get();

        $drafts = $rows->map(function ($row) {
            $data = $row->form_data ?: [];
            return [
                'db_id' => $row->id,
                'id' => $row->form_code ?: (string) $row->id,
                'survey_id' => $data['survey_id'] ?? ('SRV-' . $row->id),
                'scope' => $row->scope,
                'updated_at' => $row->updated_at ? $row->updated_at->toISOString() : null,
                'member_count' => isset($data['members']) ? count($data['members']) : 0,
                'address_preview' => $data['address_preview'] ?? ($row->title_en ?: 'Draft Survey'),
                'field_worker' => $row->field_worker_name ?: ($data['field_worker'] ?? 'Field Worker'),
                'data' => $data['data'] ?? $data,
            ];
        });

        return response()->json([
            'success' => true,
            'source' => 'MySQL database table: survey_form',
            'data' => $drafts,
        ]);
    }

    /**
     * POST /api/admin/survey-forms/drafts
     * Save/update a draft survey into MySQL table `survey_form`.
     */
    public function saveDraft(Request $request): JsonResponse
    {
        $payload = $request->all();
        $draftId = $payload['id'] ?? ('DRAFT_' . time());
        $scope = $payload['scope'] ?? 'DISTRICT';

        $record = SurveyForm::updateOrCreate(
            [
                'form_code' => (string) $draftId,
                'record_type' => 'DRAFT',
            ],
            [
                'scope' => strtoupper($scope),
                'title_en' => $payload['address_preview'] ?? 'Draft Survey',
                'field_worker_name' => $payload['field_worker'] ?? 'Field Worker',
                'form_data' => $payload,
                'status' => 'DRAFT',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Draft saved into table `survey_form`',
            'source' => 'MySQL database table: survey_form',
            'id' => $draftId,
            'db_id' => $record->id,
        ]);
    }

    /**
     * DELETE /api/admin/survey-forms/drafts/{id}
     * Delete a draft survey from MySQL table `survey_form`.
     */
    public function deleteDraft(string $id): JsonResponse
    {
        SurveyForm::where('record_type', 'DRAFT')
            ->where(function ($q) use ($id) {
                $q->where('form_code', $id)->orWhere('id', $id);
            })
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Draft deleted from table `survey_form`',
            'source' => 'MySQL database table: survey_form',
        ]);
    }

    /**
     * GET /api/admin/survey-forms/submissions
     * Fetch completed surveys from MySQL table `survey_form`.
     */
    public function getSubmissions(): JsonResponse
    {
        $rows = SurveyForm::where('record_type', 'SUBMISSION')
            ->orderBy('created_at', 'desc')
            ->get();

        $submissions = $rows->map(function ($row) {
            $data = $row->form_data ?: [];
            return [
                'db_id' => $row->id,
                'id' => $row->form_code ?: (string) $row->id,
                'survey_id' => $data['survey_id'] ?? ('SRV-' . $row->id),
                'scope' => $row->scope,
                'submitted_at' => $row->created_at ? $row->created_at->toISOString() : null,
                'member_count' => isset($data['members']) ? count($data['members']) : 0,
                'address' => $data['address'] ?? ($row->title_en ?: 'Submitted Survey'),
                'field_worker' => $row->field_worker_name ?: ($data['field_worker'] ?? 'Field Worker'),
                'household' => $data['household'] ?? null,
                'members' => $data['members'] ?? [],
            ];
        });

        return response()->json([
            'success' => true,
            'source' => 'MySQL database table: survey_form',
            'data' => $submissions,
        ]);
    }

    /**
     * POST /api/admin/survey-forms/submissions
     * Store completed voter/household survey response into MySQL table `survey_form`.
     */
    public function saveSubmission(Request $request): JsonResponse
    {
        $payload = $request->all();
        $surveyId = $payload['survey_id'] ?? $payload['id'] ?? ('SRV-' . time());
        $scope = $payload['scope'] ?? 'DISTRICT';

        $record = SurveyForm::create([
            'scope' => strtoupper($scope),
            'record_type' => 'SUBMISSION',
            'form_code' => (string) $surveyId,
            'title_en' => $payload['address'] ?? 'Survey Response',
            'field_worker_name' => $payload['field_worker'] ?? 'Field Worker',
            'form_data' => $payload,
            'status' => 'COMPLETED',
        ]);

        // If there was a draft with this ID, remove it
        if (! empty($payload['id'])) {
            SurveyForm::where('record_type', 'DRAFT')
                ->where('form_code', (string) $payload['id'])
                ->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Survey response successfully saved into table `survey_form`',
            'source' => 'MySQL database table: survey_form',
            'db_id' => $record->id,
            'survey_id' => $surveyId,
        ]);
    }
}

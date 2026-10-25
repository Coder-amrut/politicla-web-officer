<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\PublishedForm;
use App\Models\PublishedFormUser;
use App\Models\User;
use App\Models\UserProjectAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublishedFormController extends Controller
{
    /**
     * GET /api/admin/published-forms
     * List all published forms with assigned users.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PublishedForm::with(['assignedUsers' => function ($q) {
            $q->select('users.id', 'users.uuid', 'users.full_name', 'users.email', 'users.phone', 'users.profile_photo')
              ->withPivot(['id', 'role', 'status', 'assigned_at']);
        }])->orderBy('published_at', 'desc')->orderBy('id', 'desc');

        if ($request->filled('search')) {
            $term = '%' . trim($request->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('form_title', 'like', $term)
                  ->orWhere('form_title_mr', 'like', $term)
                  ->orWhere('project_name', 'like', $term)
                  ->orWhere('geographic_scope', 'like', $term);
            });
        }

        if ($request->filled('project_uuid')) {
            $query->where('project_uuid', $request->project_uuid);
        }

        if ($request->filled('scope') && $request->scope !== 'ALL') {
            $query->where('geographic_scope', $request->scope);
        }

        $forms = $query->get();

        return response()->json([
            'success' => true,
            'count'   => $forms->count(),
            'data'    => $forms,
        ]);
    }

    /**
     * GET /api/admin/published-forms/{uuid}
     * Get details of a single published form.
     */
    public function show(string $uuid): JsonResponse
    {
        $form = PublishedForm::with(['assignedUsers' => function ($q) {
            $q->select('users.id', 'users.uuid', 'users.full_name', 'users.email', 'users.phone', 'users.profile_photo')
              ->withPivot(['id', 'role', 'status', 'assigned_at']);
        }])->where('uuid', $uuid)->first();

        if (! $form) {
            return response()->json(['success' => false, 'message' => 'Published form not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $form,
        ]);
    }

    /**
     * POST /api/admin/published-forms/publish
     * Publish or update a form and store complete format directly into `published_forms` table.
     */
    public function publish(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'form_uuid'              => 'nullable|string',
            'create_new'             => 'nullable|boolean',
            'project_uuid'           => 'nullable|string',
            'project_name'           => 'nullable|string',
            'form_title'             => 'nullable|string',
            'form_title_mr'          => 'nullable|string',
            'geographic_scope'       => 'nullable|string',
            'default_fields'         => 'nullable|array',
            'custom_questions'       => 'nullable|array',
            'form_format'            => 'nullable',
            'target_households'      => 'nullable|integer',
            'status'                 => 'nullable|string',
            'user_ids'               => 'nullable|array',
            'user_ids.*'             => 'integer|exists:users,id',
            'sync_project_members'   => 'nullable|boolean',
            'assign_all_workers'     => 'nullable|boolean',
        ]);

        $project = null;
        if (! empty($validated['project_uuid'])) {
            $project = Project::where('uuid', $validated['project_uuid'])->first();
        }

        $currentUser = $request->user();
        $adminId = ($currentUser instanceof \App\Models\User)
            ? $currentUser->id
            : (\App\Models\User::where('email', $currentUser?->email)->value('id') ?? 1);

        // Default mandatory fields structure
        $defaultFields = $validated['default_fields'] ?? [
            ['id' => 'family_pramukh_name', 'label_mr' => 'कुटुंब प्रमुखाचे नाव', 'label_en' => 'Family Head Name', 'type' => 'text', 'required' => true],
            ['id' => 'total_matdar', 'label_mr' => 'एकूण मतदार संख्या', 'label_en' => 'Total Matdar', 'type' => 'number', 'required' => true],
            ['id' => 'all_members', 'label_mr' => 'सर्व सदस्यांची नावे', 'label_en' => 'All Family Members', 'type' => 'member_list', 'required' => true],
            ['id' => 'dob_and_age', 'label_mr' => 'जन्मतारीख व वय', 'label_en' => 'DOB & Auto Age', 'type' => 'date_age', 'required' => true],
            ['id' => 'has_voter_id', 'label_mr' => 'मतदार ओळखपत्र आहे का नाही', 'label_en' => 'Has Voter ID (Yes/No)', 'type' => 'voter_id_toggle', 'required' => true],
            ['id' => 'address_and_mobile', 'label_mr' => 'पत्ता व मोबाईल क्रमांक', 'label_en' => 'Address & Contact', 'type' => 'contact_info', 'required' => false],
        ];

        $projectName = !empty($validated['project_name'])
            ? trim($validated['project_name'])
            : ($project?->name ?? (!empty($validated['form_title']) ? trim($validated['form_title']) : 'सांगली मोहीम'));

        $formTitle = !empty($validated['form_title'])
            ? trim($validated['form_title'])
            : ($projectName . ' Survey Form');

        $formTitleMr = !empty($validated['form_title_mr'])
            ? trim($validated['form_title_mr'])
            : (!empty($validated['form_title']) ? trim($validated['form_title']) : ($projectName . ' सर्व्हे फॉर्म'));

        $geographicScope = !empty($validated['geographic_scope'])
            ? $validated['geographic_scope']
            : ($project?->geographic_scope ?? 'GENERAL');

        $targetHouseholds = !empty($validated['target_households']) ? (int) $validated['target_households'] : 100;
        $status = !empty($validated['status']) ? strtoupper(trim($validated['status'])) : 'PUBLISHED';

        // Format compilation
        $compiledFormat = is_array($validated['form_format'] ?? null)
            ? $validated['form_format']
            : [
                'default_fields'    => $defaultFields,
                'custom_questions'  => $validated['custom_questions'] ?? [],
                'published_at'      => Carbon::now()->toIso8601String(),
                'project_name'      => $projectName,
                'geographic_scope'  => $geographicScope,
                'target_households' => $targetHouseholds,
            ];

        // Find existing published form or create a new row in `published_forms`
        $formUuid = $validated['form_uuid'] ?? null;
        $createNew = filter_var($validated['create_new'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $publishedForm = null;
        if (!empty($formUuid) && !$createNew) {
            $publishedForm = PublishedForm::where('uuid', $formUuid)->first();
        }
        if (!$publishedForm && !$createNew) {
            if ($project) {
                $publishedForm = PublishedForm::where('project_id', $project->id)->first();
            }
            if (!$publishedForm && !empty($validated['project_uuid'])) {
                $publishedForm = PublishedForm::where('project_uuid', $validated['project_uuid'])->first();
            }
        }

        if (! $publishedForm) {
            $publishedForm = new PublishedForm();
            $publishedForm->uuid = (string) Str::uuid();
            $publishedForm->version = 1;
        } else {
            $publishedForm->version = ($publishedForm->version ?? 1) + 1;
        }

        $publishedForm->project_id       = $project?->id;
        $publishedForm->project_uuid     = $project?->uuid ?? ($validated['project_uuid'] ?? null);
        $publishedForm->project_name     = $projectName;
        $publishedForm->form_title       = $formTitle;
        $publishedForm->form_title_mr    = $formTitleMr;
        $publishedForm->geographic_scope = $geographicScope;
        $publishedForm->default_fields   = $defaultFields;
        $publishedForm->custom_questions = $validated['custom_questions'] ?? [];
        $publishedForm->form_format      = $compiledFormat;
        $publishedForm->status           = $status;
        $publishedForm->published_by     = $adminId;
        $publishedForm->published_at     = Carbon::now();
        $publishedForm->save();

        // Assign users logic:
        $assignedUserIds = [];
        if (! empty($validated['user_ids'])) {
            $assignedUserIds = $validated['user_ids'];
        } elseif (filter_var($validated['assign_all_workers'] ?? true, FILTER_VALIDATE_BOOLEAN)) {
            // Auto-assign active workers so the form is available for them to fill
            $assignedUserIds = DB::table('users')
                ->join('user_roles', 'users.id', '=', 'user_roles.user_id')
                ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                ->where('roles.name', 'WORKER')
                ->where('users.status', 'ACTIVE')
                ->pluck('users.id')
                ->toArray();

            // If empty, also check project members
            if (empty($assignedUserIds) && $project) {
                $assignedUserIds = UserProjectAssignment::where('project_id', $project->id)
                    ->whereIn('status', ['ASSIGNED', 'ACTIVE'])
                    ->pluck('user_id')
                    ->toArray();
            }
        } elseif (($validated['sync_project_members'] ?? false) && $project) {
            // Auto-sync project members
            $assignedUserIds = UserProjectAssignment::where('project_id', $project->id)
                ->whereIn('status', ['ASSIGNED', 'ACTIVE'])
                ->pluck('user_id')
                ->toArray();
        }

        if (! empty($assignedUserIds)) {
            $syncData = [];
            foreach ($assignedUserIds as $uId) {
                $syncData[$uId] = [
                    'role'        => 'WORKER',
                    'status'      => 'ACTIVE',
                    'assigned_by' => $adminId,
                    'assigned_at' => Carbon::now(),
                ];
            }
            $publishedForm->assignedUsers()->sync($syncData);
        }

        $publishedForm->assigned_users_count = $publishedForm->assignedUsers()->count();
        $publishedForm->save();

        // Also update Project status to ACTIVE if project exists
        if ($project) {
            $project->status = 'ACTIVE';
            $project->save();
        }

        // Return fresh model with users
        $publishedForm->load(['assignedUsers' => function ($q) {
            $q->select('users.id', 'users.uuid', 'users.full_name', 'users.email', 'users.phone', 'users.profile_photo')
              ->withPivot(['id', 'role', 'status', 'assigned_at']);
        }]);

        return response()->json([
            'success' => true,
            'message' => "सर्व्हे फॉर्म '{$formTitleMr}' यशस्वीरित्या published_forms डेटाबेस टेबलमध्ये जतन झाला!",
            'data'    => $publishedForm,
        ]);
    }

    /**
     * GET /api/admin/published-forms/{uuid}/users
     * Get all users with assignment status for this published form.
     */
    public function getAssignableUsers(string $uuid, Request $request): JsonResponse
    {
        $publishedForm = PublishedForm::where('uuid', $uuid)->firstOrFail();

        $currentlyAssignedIds = $publishedForm->assignedUsers()->pluck('users.id')->toArray();

        $usersQuery = User::where('status', 'ACTIVE')
            ->select('id', 'uuid', 'full_name', 'email', 'phone', 'profile_photo');

        if ($request->filled('q')) {
            $term = '%' . trim($request->q) . '%';
            $usersQuery->where(function ($q) use ($term) {
                $q->where('full_name', 'like', $term)
                  ->orWhere('phone', 'like', $term)
                  ->orWhere('email', 'like', $term);
            });
        }

        $users = $usersQuery->orderBy('full_name')->get()->map(function ($u) use ($currentlyAssignedIds) {
            $u->is_assigned = in_array($u->id, $currentlyAssignedIds);
            return $u;
        });

        return response()->json([
            'success'               => true,
            'published_form_id'     => $publishedForm->id,
            'published_form_title'  => $publishedForm->form_title,
            'assigned_count'        => count($currentlyAssignedIds),
            'total_available_users' => $users->count(),
            'data'                  => $users,
        ]);
    }

    /**
     * PUT /api/admin/published-forms/{uuid}/users
     * Update/assign user access to this published form.
     */
    public function assignUsers(string $uuid, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_ids'   => 'present|array',
            'user_ids.*' => 'integer|exists:users,id',
            'role'       => 'nullable|string',
            'status'     => 'nullable|string',
        ]);

        $publishedForm = PublishedForm::where('uuid', $uuid)->firstOrFail();
        $currentUser = $request->user();
        $adminId = ($currentUser instanceof \App\Models\User)
            ? $currentUser->id
            : (\App\Models\User::where('email', $currentUser?->email)->value('id') ?? 1);

        $role = $validated['role'] ?? 'WORKER';
        $status = $validated['status'] ?? 'ACTIVE';

        $syncPayload = [];
        foreach ($validated['user_ids'] as $userId) {
            $syncPayload[$userId] = [
                'role'        => $role,
                'status'      => $status,
                'assigned_by' => $adminId,
                'assigned_at' => Carbon::now(),
            ];
        }

        $publishedForm->assignedUsers()->sync($syncPayload);

        // Update count
        $count = count($validated['user_ids']);
        $publishedForm->assigned_users_count = $count;
        $publishedForm->save();

        $publishedForm->load(['assignedUsers' => function ($q) {
            $q->select('users.id', 'users.uuid', 'users.full_name', 'users.email', 'users.phone', 'users.profile_photo')
              ->withPivot(['id', 'role', 'status', 'assigned_at']);
        }]);

        return response()->json([
            'success' => true,
            'message' => 'User access updated successfully for this published form.',
            'data'    => $publishedForm,
        ]);
    }

    /**
     * DELETE /api/admin/published-forms/{uuid}/users/{userId}
     * Revoke access for a specific user from this published form.
     */
    public function removeUser(string $uuid, int $userId): JsonResponse
    {
        $publishedForm = PublishedForm::where('uuid', $uuid)->firstOrFail();

        $publishedForm->assignedUsers()->detach($userId);

        $publishedForm->assigned_users_count = $publishedForm->assignedUsers()->count();
        $publishedForm->save();

        return response()->json([
            'success' => true,
            'message' => 'User access revoked from published form.',
            'assigned_users_count' => $publishedForm->assigned_users_count,
        ]);
    }

    /**
     * PATCH /api/admin/published-forms/{uuid}/status
     * Update the status of a published form (e.g. PUBLISHED, ACTIVE, INACTIVE, DRAFT, CLOSED).
     */
    public function updateStatus(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:PUBLISHED,ACTIVE,INACTIVE,DRAFT,CLOSED',
        ]);

        $publishedForm = PublishedForm::with(['assignedUsers' => function ($q) {
            $q->select('users.id', 'users.uuid', 'users.full_name', 'users.email', 'users.phone', 'users.profile_photo')
              ->withPivot(['id', 'role', 'status', 'assigned_at']);
        }])->where('uuid', $uuid)->firstOrFail();

        $publishedForm->status = strtoupper($validated['status']);
        $publishedForm->save();

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully to ' . $publishedForm->status,
            'data'    => $publishedForm,
        ]);
    }

    /**
     * DELETE /api/admin/published-forms/{uuid}
     * Delete or unpublish a form.
     */
    public function destroy(string $uuid): JsonResponse
    {
        $publishedForm = PublishedForm::where('uuid', $uuid)->firstOrFail();
        $publishedForm->delete();

        return response()->json([
            'success' => true,
            'message' => 'Published form deleted successfully.',
        ]);
    }
}

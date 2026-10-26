<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProjectRequest;
use App\Http\Requests\Admin\UpdateProjectRequest;
use App\Http\Requests\Admin\UpdateProjectStatusRequest;
use App\Models\Project;
use App\Models\User;
use App\Models\UserProjectAssignment;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    // -------------------------------------------------------------------------
    // GET /api/admin/projects
    // List all projects — paginated, with optional filters.
    // -------------------------------------------------------------------------

    public function index(Request $request): JsonResponse
    {
        $query = Project::with('creator')
            ->withCount('memberAssignments')
            ->orderBy('created_at', 'desc');

        // Optional filter: ?status=DRAFT|ACTIVE|COMPLETED|ARCHIVED
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        // Optional search: ?search=project_name_or_type
        if ($request->filled('search')) {
            $term = '%' . $request->query('search') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('project_type', 'like', $term);
            });
        }

        $perPage  = min((int) $request->query('per_page', 15), 100);
        $projects = $query->paginate($perPage);

        return response()->json([
            'data'       => $projects->map(fn (Project $p) => $this->formatProject($p)),
            'total'      => $projects->total(),
            'per_page'   => $projects->perPage(),
            'page'       => $projects->currentPage(),
            'last_page'  => $projects->lastPage(),
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /api/admin/projects
    // Create a new project.
    // -------------------------------------------------------------------------

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $admin     = $request->user();

        // Resolve valid User ID in users table (handling both User and Admin models)
        $creatorId = ($admin instanceof User)
            ? $admin->id
            : (User::where('email', $admin->email)->value('id')
                ?? User::whereHas('roles', fn ($q) => $q->where('name', 'ADMIN'))->value('id')
                ?? $admin->id);

        \DB::beginTransaction();
        try {
            $geoContext = $validated['geographic_context'] ?? [];
            $candidates = $validated['candidates'] ?? ($geoContext['candidates'] ?? []);
            if (!empty($candidates)) {
                $geoContext['candidates'] = $candidates;
            }

            $project = Project::create([
                'uuid'         => (string) Str::uuid(),
                'name'         => $validated['name'],
                'project_type' => $validated['project_type'] ?? ($validated['geographic_scope'] ?? 'GENERAL'),
                'description'  => $validated['description'] ?? null,
                'status'       => $validated['status'] ?? ProjectStatus::DRAFT->value,
                'start_date'   => $validated['start_date'] ?? null,
                'end_date'     => $validated['end_date'] ?? null,
                'created_by'   => $creatorId,
                'assigned_member_count' => 0,
                'assigned_member_name' => '',
                'geographic_scope' => $validated['geographic_scope'] ?? null,
                'geographic_code' => $validated['geographic_code'] ?? null,
                'geographic_name_en' => $validated['geographic_name_en'] ?? null,
                'geographic_name_mr' => $validated['geographic_name_mr'] ?? null,
                'geographic_context' => $geoContext,
            ]);

            // If candidates are supplied, sync into EVM voting tables for this election
            if (!empty($candidates) && \Schema::hasTable('karkarta_evm_candidates')) {
                $electionId = 1;
                if (\Schema::hasTable('karkarta_evm_elections')) {
                    $electionId = \DB::table('karkarta_evm_elections')->insertGetId([
                        'name' => $project->name,
                        'status' => 'ACTIVE',
                        'date_time' => ($project->start_date ? 'दिनांक: ' . $project->start_date : 'मतदान तारीख लवकरच जाहीर होईल'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                foreach ($candidates as $idx => $cand) {
                    if (empty($cand['candidate_name'])) continue;
                    \DB::table('karkarta_evm_candidates')->insert([
                        'election_id' => $electionId,
                        'serial_number' => $cand['serial_number'] ?? ($idx + 1),
                        'candidate_name' => $cand['candidate_name'],
                        'party_name' => $cand['party_name'] ?? 'अपक्ष',
                        'candidate_photo' => $cand['candidate_photo'] ?? null,
                        'party_symbol' => $cand['party_symbol'] ?? null,
                        'position' => $cand['position'] ?? ($project->project_type ?: 'उमेदवार'),
                        'highlighted' => !empty($cand['highlighted']),
                        'voice_message' => $cand['voice_message'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            AuditService::log(
                action:     'PROJECT_CREATED',
                userId:     $creatorId,
                entityType: 'projects',
                entityId:   (string) $project->id,
                newData:    [
                    'uuid'         => $project->uuid,
                    'name'         => $project->name,
                    'project_type' => $project->project_type,
                    'status'       => $project->status,
                    'geographic_scope' => $project->geographic_scope,
                    'geographic_code' => $project->geographic_code,
                    'geographic_name_en' => $project->geographic_name_en,
                    'geographic_name_mr' => $project->geographic_name_mr,
                    'geographic_context' => $project->geographic_context,
                    'candidates_count'   => count($candidates),
                ],
                request:    $request,
            );

            \DB::commit();
        } catch (\Throwable $e) {
            \DB::rollBack();
            throw $e;
        }

        $project->load('creator');

        return response()->json([
            'message' => 'Project created successfully.',
            'project' => $this->formatProject($project),
        ], 201);
    }

    // -------------------------------------------------------------------------
    // GET /api/admin/projects/{uuid}
    // Retrieve a single project by UUID.
    // -------------------------------------------------------------------------

    public function show(string $uuid): JsonResponse
    {
        $project = Project::where('uuid', $uuid)->with('creator')->firstOrFail();

        return response()->json([
            'project' => $this->formatProject($project),
        ]);
    }

    public function assignableMembers(string $uuid): JsonResponse
    {
        $project = Project::where('uuid', $uuid)->firstOrFail();
        $assignments = UserProjectAssignment::where('project_id', $project->id)
            ->get(['user_id', 'status', 'assigned_at', 'created_at'])
            ->keyBy('user_id');

        $members = User::with('roles:id,name,display_name')
            ->orderBy('full_name')
            ->get(['id', 'uuid', 'full_name', 'email', 'phone', 'status'])
            ->map(function (User $user) use ($assignments) {
                $assignment = $assignments->get($user->id);
                $roleNames = $user->roles->pluck('name');
                return [
                    'id' => $user->id,
                    'uuid' => $user->uuid,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $roleNames->first() ?? 'USER',
                    'roles' => $roleNames,
                    'user_status' => $user->status,
                    'assigned' => $assignment !== null,
                    'status' => $assignment ? (($assignment->status === 'ACTIVE' || !$assignment->status) ? 'ASSIGNED' : $assignment->status) : null,
                    'assigned_at' => $assignment ? ($assignment->assigned_at ?: $assignment->created_at?->toDateTimeString()) : null,
                ];
            });

        return response()->json(['data' => $members]);
    }

    public function assignMembers(Request $request, string $uuid): JsonResponse
    {
        $project = Project::where('uuid', $uuid)->firstOrFail();
        if ($project->status === ProjectStatus::COMPLETED || $project->status === ProjectStatus::ARCHIVED || (is_string($project->status) && in_array($project->status, ['COMPLETED', 'ARCHIVED'], true))) {
            return response()->json(['message' => 'Cannot modify members for a completed project.'], 422);
        }

        if ($request->has('members') && is_array($request->input('members'))) {
            $validated = $request->validate([
                'members' => ['required', 'array'],
                'members.*.user_id' => ['required', 'integer', 'exists:users,id'],
                'members.*.status' => ['nullable', 'string', 'in:ASSIGNED,PENDING,COMPLETE'],
            ]);
            $memberData = collect($validated['members'])->keyBy('user_id');
            $userIds = $memberData->keys()->all();
        } else {
            $validated = $request->validate([
                'user_ids' => ['required', 'array'],
                'user_ids.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
            ]);
            $userIds = $validated['user_ids'];
            $memberData = collect($userIds)->mapWithKeys(fn ($id) => [$id => ['user_id' => $id, 'status' => 'ASSIGNED']]);
        }

        $eligibleCount = User::whereIn('id', $userIds)->count();

        if ($eligibleCount !== count($userIds)) {
            return response()->json(['message' => 'One or more selected users do not exist.'], 422);
        }

        \DB::transaction(function () use ($project, $userIds, $memberData, $request) {
            UserProjectAssignment::where('project_id', $project->id)->whereNotIn('user_id', $userIds)->delete();
            $currentUser = $request->user();
            $assignedById = ($currentUser instanceof \App\Models\User)
                ? $currentUser->id
                : (\App\Models\User::where('email', $currentUser?->email)->value('id') ?? 1);

            foreach ($memberData as $userId => $item) {
                $status = $item['status'] ?? 'ASSIGNED';
                UserProjectAssignment::updateOrCreate(
                    ['project_id' => $project->id, 'user_id' => $userId],
                    [
                        'assigned_by' => $assignedById,
                        'status' => $status,
                        'assigned_at' => now(),
                    ]
                );
            }
            $this->syncAssignedMemberStats($project);
        });

        return response()->json(['message' => 'Project members assigned successfully.']);
    }

    public function updateMemberStatus(Request $request, string $uuid, int $userId): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:ASSIGNED,PENDING,COMPLETE'],
        ]);

        $project = Project::where('uuid', $uuid)->firstOrFail();
        if ($project->status === ProjectStatus::COMPLETED || $project->status === ProjectStatus::ARCHIVED || (is_string($project->status) && in_array($project->status, ['COMPLETED', 'ARCHIVED'], true))) {
            return response()->json(['message' => 'Cannot update member status of a completed project.'], 422);
        }
        $assignment = UserProjectAssignment::where('project_id', $project->id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $assignment->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Member status updated successfully.',
            'status' => $assignment->status,
        ]);
    }

    public function removeMember(string $uuid, int $userId): JsonResponse
    {
        $project = Project::where('uuid', $uuid)->firstOrFail();
        if ($project->status === ProjectStatus::COMPLETED || $project->status === ProjectStatus::ARCHIVED || (is_string($project->status) && in_array($project->status, ['COMPLETED', 'ARCHIVED'], true))) {
            return response()->json(['message' => 'Cannot modify members of a completed project.'], 422);
        }
        UserProjectAssignment::where('project_id', $project->id)
            ->where('user_id', $userId)
            ->delete();

        $this->syncAssignedMemberStats($project);

        return response()->json(['message' => 'Member removed from project successfully.']);
    }

    // -------------------------------------------------------------------------
    // PATCH /api/admin/projects/{uuid}
    // Update project metadata (name, type, description, dates).
    // Status changes go through the dedicated /status endpoint.
    // -------------------------------------------------------------------------

    public function update(UpdateProjectRequest $request, string $uuid): JsonResponse
    {
        $project   = Project::where('uuid', $uuid)->firstOrFail();
        if ($project->status === ProjectStatus::COMPLETED || $project->status === ProjectStatus::ARCHIVED || (is_string($project->status) && in_array($project->status, ['COMPLETED', 'ARCHIVED'], true))) {
            return response()->json(['message' => 'Cannot modify details of a completed project. Only project deletion is permitted.'], 422);
        }
        $validated = $request->validated();
        $admin     = $request->user();

        $oldData = [
            'name'         => $project->name,
            'project_type' => $project->project_type,
            'description'  => $project->description,
            'start_date'   => $project->start_date?->toDateString(),
            'end_date'     => $project->end_date?->toDateString(),
            'geographic_scope' => $project->geographic_scope,
            'geographic_code' => $project->geographic_code,
            'geographic_name_en' => $project->geographic_name_en,
            'geographic_name_mr' => $project->geographic_name_mr,
            'geographic_context' => $project->geographic_context,
            'geographic_scope' => $project->geographic_scope,
            'geographic_code' => $project->geographic_code,
            'geographic_name_en' => $project->geographic_name_en,
            'geographic_name_mr' => $project->geographic_name_mr,
            'geographic_context' => $project->geographic_context,
            'geographic_scope' => $project->geographic_scope,
            'geographic_code' => $project->geographic_code,
            'geographic_name_en' => $project->geographic_name_en,
            'geographic_name_mr' => $project->geographic_name_mr,
            'geographic_context' => $project->geographic_context,
        ];

        \DB::beginTransaction();
        try {
            $project->update($validated);

            AuditService::log(
                action:     'PROJECT_UPDATED',
                userId:     $admin->id,
                entityType: 'projects',
                entityId:   (string) $project->id,
                oldData:    $oldData,
                newData:    $validated,
                request:    $request,
            );

            \DB::commit();
        } catch (\Throwable $e) {
            \DB::rollBack();
            throw $e;
        }

        $project->load('creator');

        return response()->json([
            'message' => 'Project updated successfully.',
            'project' => $this->formatProject($project),
        ]);
    }

    // -------------------------------------------------------------------------
    // PATCH /api/admin/projects/{uuid}/status
    // Transition project to the next allowed status.
    // -------------------------------------------------------------------------

    public function updateStatus(UpdateProjectStatusRequest $request, string $uuid): JsonResponse
    {
        $project    = Project::where('uuid', $uuid)->firstOrFail();
        $validated  = $request->validated();
        $admin      = $request->user();

        // Resolve target status enum case
        $targetStatus = ProjectStatus::from($validated['status']);

        // Enforce workflow transition rules
        $currentStatus = $project->status; // already cast to ProjectStatus enum via model

        if (! $currentStatus->canTransitionTo($targetStatus)) {
            return response()->json([
                'message' => sprintf(
                    'Cannot transition project from %s to %s. Allowed transitions from %s: [%s].',
                    $currentStatus->value,
                    $targetStatus->value,
                    $currentStatus->value,
                    implode(', ', array_map(fn (ProjectStatus $s) => $s->value, $currentStatus->allowedTransitions()))
                ),
            ], 409);
        }

        \DB::beginTransaction();
        try {
            $project->update(['status' => $targetStatus->value]);

            AuditService::log(
                action:     'PROJECT_STATUS_CHANGED',
                userId:     $admin->id,
                entityType: 'projects',
                entityId:   (string) $project->id,
                oldData:    ['status' => $currentStatus->value],
                newData:    ['status' => $targetStatus->value],
                request:    $request,
            );

            \DB::commit();
        } catch (\Throwable $e) {
            \DB::rollBack();
            throw $e;
        }

        $project->load('creator');

        return response()->json([
            'message' => sprintf('Project status changed to %s.', $targetStatus->value),
            'project' => $this->formatProject($project),
        ]);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/admin/projects/{uuid}
    // Delete a project and its associated assignments/geographies cleanly.
    // -------------------------------------------------------------------------

    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $project = Project::where('uuid', $uuid)->firstOrFail();
        $admin   = $request->user();

        \DB::beginTransaction();
        try {
            // Delete dependent records cleanly
            \DB::table('attendance_events')->where('project_id', $project->id)->delete();
            \DB::table('user_geography_assignments')->where('project_id', $project->id)->delete();
            \DB::table('user_project_assignments')->where('project_id', $project->id)->delete();
            \DB::table('geographies')->where('project_id', $project->id)->delete();

            $projectData = [
                'id'           => $project->id,
                'uuid'         => $project->uuid,
                'name'         => $project->name,
                'project_type' => $project->project_type,
                'status'       => $project->status instanceof ProjectStatus ? $project->status->value : $project->status,
            ];

            $project->delete();

            AuditService::log(
                action:     'PROJECT_DELETED',
                userId:     $admin->id,
                entityType: 'projects',
                entityId:   (string) $projectData['id'],
                oldData:    $projectData,
                request:    $request,
            );

            \DB::commit();
        } catch (\Throwable $e) {
            \DB::rollBack();
            throw $e;
        }

        return response()->json([
            'message' => 'Project deleted successfully.',
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Synchronize assigned member count and names into the projects table and promote DRAFT to ACTIVE.
     */
    public function syncAssignedMemberStats(Project $project): void
    {
        $assignedUsers = User::whereIn('id', function ($query) use ($project) {
            $query->select('user_id')->from('user_project_assignments')->where('project_id', $project->id);
        })->orderBy('full_name')->get(['id', 'full_name']);

        $count = $assignedUsers->count();
        $names = $assignedUsers->pluck('full_name')->join(', ');

        $updateData = [
            'assigned_member_count' => $count,
            'assigned_member_name'  => mb_substr($names, 0, 255),
        ];

        $currentStatus = $project->status instanceof ProjectStatus ? $project->status->value : (string) $project->status;
        if ($currentStatus === 'DRAFT' && $count > 0) {
            $updateData['status'] = ProjectStatus::ACTIVE->value;
        }

        $project->update($updateData);
    }

    /**
     * Return a consistent project representation — never expose internal IDs raw.
     */
    private function formatProject(Project $project): array
    {
        return [
            'uuid'         => $project->uuid,
            'name'         => $project->name,
            'project_type' => $project->project_type,
            'description'  => $project->description,
            'status'       => $project->status instanceof ProjectStatus
                                ? $project->status->value
                                : $project->status,
            'start_date'   => $project->start_date?->toDateString(),
            'end_date'     => $project->end_date?->toDateString(),
            'assigned_member_count' => (int) ($project->assigned_member_count ?? $project->member_assignments_count ?? $project->memberAssignments()->count()),
            'assigned_member_name'  => (string) ($project->assigned_member_name ?? ''),
            'assigned_members_count' => (int) ($project->assigned_member_count ?? $project->member_assignments_count ?? $project->memberAssignments()->count()),
            'created_by'   => $project->creator ? [
                'id'        => $project->creator->id,
                'uuid'      => $project->creator->uuid,
                'full_name' => $project->creator->full_name,
            ] : null,
            'geographic_scope' => $project->geographic_scope,
            'geographic_code'  => $project->geographic_code,
            'geographic_name_en' => $project->geographic_name_en,
            'geographic_name_mr' => $project->geographic_name_mr,
            'geographic_context' => $project->geographic_context,
            'created_at'   => $project->created_at?->toISOString(),
            'updated_at'   => $project->updated_at?->toISOString(),
        ];
    }
}

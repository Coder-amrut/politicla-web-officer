<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GeographyStatus;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGeographyRequest;
use App\Http\Requests\Admin\UpdateGeographyRequest;
use App\Http\Requests\Admin\UpdateGeographyStatusRequest;
use App\Models\Geography;
use App\Models\GeographyType;
use App\Models\Project;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GeographyController extends Controller
{
    // -------------------------------------------------------------------------
    // GET /api/admin/projects/{projectUuid}/geographies
    // List all geographies within a project — filterable & paginated.
    // -------------------------------------------------------------------------

    public function index(Request $request, string $projectUuid): JsonResponse
    {
        $project = Project::where('uuid', $projectUuid)->firstOrFail();

        $query = Geography::where('project_id', $project->id)
            ->with(['project', 'type', 'parent.type'])
            ->orderBy('id', 'asc');

        // Filter by geography_type_id
        if ($request->filled('geography_type_id')) {
            $query->where('geography_type_id', (int) $request->query('geography_type_id'));
        }

        // Filter by status: ACTIVE | INACTIVE
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        // Filter by parent_uuid
        if ($request->filled('parent_uuid')) {
            $parent = Geography::where('project_id', $project->id)
                ->where('uuid', $request->query('parent_uuid'))
                ->first();

            if ($parent) {
                $query->where('parent_id', $parent->id);
            } else {
                $query->whereRaw('1 = 0'); // No matching parent in this project
            }
        }

        // Filter for top-level only (no parent)
        if ($request->boolean('top_level_only')) {
            $query->whereNull('parent_id');
        }

        // Search by name or code
        if ($request->filled('search')) {
            $term = '%' . $request->query('search') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('code', 'like', $term);
            });
        }

        $perPage     = min((int) $request->query('per_page', 50), 100);
        $geographies = $query->paginate($perPage);

        return response()->json([
            'data'      => $geographies->map(fn (Geography $g) => $this->formatGeography($g)),
            'total'     => $geographies->total(),
            'per_page'  => $geographies->perPage(),
            'page'      => $geographies->currentPage(),
            'last_page' => $geographies->lastPage(),
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /api/admin/projects/{projectUuid}/geographies
    // Create a new geography node within a project.
    // -------------------------------------------------------------------------

    public function store(StoreGeographyRequest $request, string $projectUuid): JsonResponse
    {
        $project = Project::where('uuid', $projectUuid)->firstOrFail();

        if ($project->status === ProjectStatus::ARCHIVED || $project->status === ProjectStatus::COMPLETED || (is_string($project->status) && in_array($project->status, ['ARCHIVED', 'COMPLETED'], true))) {
            return response()->json([
                'message' => 'Cannot add geographies to a completed or archived project.',
            ], 422);
        }

        $validated = $request->validated();
        $admin     = $request->user();
        $childType = GeographyType::findOrFail($validated['geography_type_id']);

        $parentId = null;
        if (! empty($validated['parent_uuid'])) {
            $parent = Geography::where('project_id', $project->id)
                ->where('uuid', $validated['parent_uuid'])
                ->with('type')
                ->first();

            if (! $parent) {
                return response()->json([
                    'message' => 'Parent geography must belong to the same project.',
                    'errors'  => ['parent_uuid' => ['Parent geography does not belong to this project or does not exist.']],
                ], 422);
            }

            if ($parent->type->level_order >= $childType->level_order) {
                return response()->json([
                    'message' => 'Parent geography level must be higher than the child geography level in the hierarchy.',
                    'errors'  => ['parent_uuid' => ['Parent level must precede child level in the hierarchy.']],
                ], 422);
            }

            $parentId = $parent->id;
        }

        DB::beginTransaction();
        try {
            $geography = Geography::create([
                'uuid'              => (string) Str::uuid(),
                'project_id'        => $project->id,
                'geography_type_id' => $childType->id,
                'parent_id'         => $parentId,
                'name'              => $validated['name'],
                'code'              => $validated['code'] ?? null,
                'latitude'          => $validated['latitude'] ?? null,
                'longitude'         => $validated['longitude'] ?? null,
                'status'            => $validated['status'] ?? GeographyStatus::ACTIVE->value,
            ]);

            AuditService::log(
                action:     'GEOGRAPHY_CREATED',
                userId:     $admin->id,
                entityType: 'geographies',
                entityId:   (string) $geography->id,
                newData:    [
                    'uuid'              => $geography->uuid,
                    'project_uuid'      => $project->uuid,
                    'name'              => $geography->name,
                    'geography_type_id' => $geography->geography_type_id,
                    'parent_id'         => $geography->parent_id,
                    'status'            => $geography->status instanceof GeographyStatus ? $geography->status->value : $geography->status,
                ],
                request:    $request,
            );

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $geography->load(['project', 'type', 'parent.type']);

        return response()->json([
            'message'   => 'Geography created successfully.',
            'geography' => $this->formatGeography($geography),
        ], 201);
    }

    // -------------------------------------------------------------------------
    // GET /api/admin/projects/{projectUuid}/geographies/{geographyUuid}
    // Retrieve single geography node details including parent and immediate children.
    // -------------------------------------------------------------------------

    public function show(string $projectUuid, string $geographyUuid): JsonResponse
    {
        $project = Project::where('uuid', $projectUuid)->firstOrFail();

        $geography = Geography::where('project_id', $project->id)
            ->where('uuid', $geographyUuid)
            ->with(['project', 'type', 'parent.type', 'children.type'])
            ->firstOrFail();

        return response()->json([
            'geography' => $this->formatGeography($geography, includeChildren: true),
        ]);
    }

    // -------------------------------------------------------------------------
    // PATCH /api/admin/projects/{projectUuid}/geographies/{geographyUuid}
    // Update geography metadata (name, code, coordinates, parent).
    // -------------------------------------------------------------------------

    public function update(UpdateGeographyRequest $request, string $projectUuid, string $geographyUuid): JsonResponse
    {
        $project = Project::where('uuid', $projectUuid)->firstOrFail();

        if ($project->status === ProjectStatus::ARCHIVED || $project->status === ProjectStatus::COMPLETED || (is_string($project->status) && in_array($project->status, ['ARCHIVED', 'COMPLETED'], true))) {
            return response()->json([
                'message' => 'Cannot update geographies of a completed or archived project.',
            ], 422);
        }

        $geography = Geography::where('project_id', $project->id)
            ->where('uuid', $geographyUuid)
            ->with(['project', 'type', 'parent.type'])
            ->firstOrFail();

        $validated = $request->validated();
        $admin     = $request->user();

        $oldData = [
            'name'      => $geography->name,
            'code'      => $geography->code,
            'latitude'  => $geography->latitude,
            'longitude' => $geography->longitude,
            'parent_id' => $geography->parent_id,
        ];

        $updateData = [];

        if (array_key_exists('name', $validated)) {
            $updateData['name'] = $validated['name'];
        }
        if (array_key_exists('code', $validated)) {
            $updateData['code'] = $validated['code'];
        }
        if (array_key_exists('latitude', $validated)) {
            $updateData['latitude'] = $validated['latitude'];
        }
        if (array_key_exists('longitude', $validated)) {
            $updateData['longitude'] = $validated['longitude'];
        }

        if ($request->has('parent_uuid')) {
            $parentUuid = $validated['parent_uuid'];

            if (empty($parentUuid)) {
                $updateData['parent_id'] = null;
            } else {
                if ($parentUuid === $geography->uuid) {
                    return response()->json([
                        'message' => 'A geography cannot be its own parent.',
                        'errors'  => ['parent_uuid' => ['Self-referencing parent is not allowed.']],
                    ], 422);
                }

                $parent = Geography::where('project_id', $project->id)
                    ->where('uuid', $parentUuid)
                    ->with('type')
                    ->first();

                if (! $parent) {
                    return response()->json([
                        'message' => 'Parent geography must belong to the same project.',
                        'errors'  => ['parent_uuid' => ['Parent geography does not belong to this project or does not exist.']],
                    ], 422);
                }

                if ($parent->type->level_order >= $geography->type->level_order) {
                    return response()->json([
                        'message' => 'Parent geography level must be higher than the child geography level in the hierarchy.',
                        'errors'  => ['parent_uuid' => ['Parent level must precede child level in the hierarchy.']],
                    ], 422);
                }

                $updateData['parent_id'] = $parent->id;
            }
        }

        DB::beginTransaction();
        try {
            $geography->update($updateData);

            AuditService::log(
                action:     'GEOGRAPHY_UPDATED',
                userId:     $admin->id,
                entityType: 'geographies',
                entityId:   (string) $geography->id,
                oldData:    $oldData,
                newData:    $updateData,
                request:    $request,
            );

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $geography->load(['project', 'type', 'parent.type']);

        return response()->json([
            'message'   => 'Geography updated successfully.',
            'geography' => $this->formatGeography($geography),
        ]);
    }

    // -------------------------------------------------------------------------
    // PATCH /api/admin/projects/{projectUuid}/geographies/{geographyUuid}/status
    // Toggle geography status between ACTIVE and INACTIVE.
    // -------------------------------------------------------------------------

    public function updateStatus(UpdateGeographyStatusRequest $request, string $projectUuid, string $geographyUuid): JsonResponse
    {
        $project = Project::where('uuid', $projectUuid)->firstOrFail();

        $geography = Geography::where('project_id', $project->id)
            ->where('uuid', $geographyUuid)
            ->with(['project', 'type', 'parent.type'])
            ->firstOrFail();

        $validated    = $request->validated();
        $admin        = $request->user();
        $targetStatus = GeographyStatus::from($validated['status']);
        $currentValue = $geography->status instanceof GeographyStatus ? $geography->status->value : $geography->status;

        DB::beginTransaction();
        try {
            $geography->update(['status' => $targetStatus->value]);

            AuditService::log(
                action:     'GEOGRAPHY_STATUS_CHANGED',
                userId:     $admin->id,
                entityType: 'geographies',
                entityId:   (string) $geography->id,
                oldData:    ['status' => $currentValue],
                newData:    ['status' => $targetStatus->value],
                request:    $request,
            );

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $geography->load(['project', 'type', 'parent.type']);

        return response()->json([
            'message'   => sprintf('Geography status changed to %s.', $targetStatus->value),
            'geography' => $this->formatGeography($geography),
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Format a geography model instance for API responses.
     */
    private function formatGeography(Geography $g, bool $includeChildren = false): array
    {
        $formatted = [
            'uuid'         => $g->uuid,
            'project_uuid' => $g->project?->uuid,
            'name'         => $g->name,
            'code'         => $g->code,
            'latitude'     => $g->latitude !== null ? (float) $g->latitude : null,
            'longitude'    => $g->longitude !== null ? (float) $g->longitude : null,
            'status'       => $g->status instanceof GeographyStatus ? $g->status->value : $g->status,
            'type'         => $g->type ? [
                'id'           => $g->type->id,
                'name'         => $g->type->name,
                'display_name' => $g->type->display_name,
                'level_order'  => (int) $g->type->level_order,
            ] : null,
            'parent'       => $g->parent ? [
                'uuid'         => $g->parent->uuid,
                'name'         => $g->parent->name,
                'code'         => $g->parent->code,
                'type'         => $g->parent->type ? [
                    'id'           => $g->parent->type->id,
                    'name'         => $g->parent->type->name,
                    'display_name' => $g->parent->type->display_name,
                    'level_order'  => (int) $g->parent->type->level_order,
                ] : null,
            ] : null,
            'created_at'   => $g->created_at?->toISOString(),
            'updated_at'   => $g->updated_at?->toISOString(),
        ];

        if ($includeChildren && $g->relationLoaded('children')) {
            $formatted['children'] = $g->children->map(fn (Geography $c) => [
                'uuid'   => $c->uuid,
                'name'   => $c->name,
                'code'   => $c->code,
                'status' => $c->status instanceof GeographyStatus ? $c->status->value : $c->status,
                'type'   => $c->type ? [
                    'id'           => $c->type->id,
                    'name'         => $c->type->name,
                    'display_name' => $c->type->display_name,
                    'level_order'  => (int) $c->type->level_order,
                ] : null,
            ]);
        }

        return $formatted;
    }
}

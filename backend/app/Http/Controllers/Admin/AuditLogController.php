<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * GET /api/admin/audit-logs
     * Return a limited, paginated activity feed for administrators.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $logs = AuditLog::query()
            ->leftJoin('users', 'users.id', '=', 'audit_logs.user_id')
            ->select([
                'audit_logs.id', 'audit_logs.user_id', 'users.full_name as user_name',
                'audit_logs.action', 'audit_logs.entity_type', 'audit_logs.entity_id',
                'audit_logs.old_data', 'audit_logs.new_data',
                'audit_logs.ip_address', 'audit_logs.created_at',
            ])
            ->orderByDesc('audit_logs.created_at')
            ->paginate((int) ($validated['per_page'] ?? 30));

        $userIds = [];
        $projectIds = [];
        foreach ($logs as $log) {
            $eType = strtolower($log->entity_type ?? '');
            if ($eType === 'users' || $eType === 'user') {
                $userIds[] = (int) $log->entity_id;
            } elseif ($eType === 'projects' || $eType === 'project') {
                $projectIds[] = (int) $log->entity_id;
            }
        }

        $targetUsers = !empty($userIds) ? \Illuminate\Support\Facades\DB::table('users')->whereIn('id', array_unique($userIds))->pluck('full_name', 'id') : collect();
        $targetProjects = !empty($projectIds) ? \Illuminate\Support\Facades\DB::table('projects')->whereIn('id', array_unique($projectIds))->pluck('name', 'id') : collect();

        return response()->json([
            'data' => $logs->getCollection()->map(function ($log) use ($targetUsers, $targetProjects) {
                $eType = strtolower($log->entity_type ?? '');
                $targetName = null;
                if ($eType === 'users' || $eType === 'user') {
                    $targetName = $targetUsers[$log->entity_id] ?? null;
                } elseif ($eType === 'projects' || $eType === 'project') {
                    $targetName = $targetProjects[$log->entity_id] ?? null;
                }

                return [
                    'id' => $log->id,
                    'user_id' => $log->user_id,
                    'user' => $log->user_name ? ['full_name' => $log->user_name] : null,
                    'action' => $log->action,
                    'entity_type' => $log->entity_type,
                    'entity_id' => $log->entity_id,
                    'target_name' => $targetName,
                    'old_data' => $log->old_data,
                    'new_data' => $log->new_data,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at,
                ];
            }),
            'total' => $logs->total(),
            'per_page' => $logs->perPage(),
            'page' => $logs->currentPage(),
            'last_page' => $logs->lastPage(),
        ]);
    }
}

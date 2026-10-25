<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Throwable;

class AuditService
{
    /**
     * Record an administrative or authentication event.
     *
     * Matches the actual audit_logs schema:
     *   user_id, action, entity_type, entity_id,
     *   old_data (json), new_data (json), ip_address, user_agent
     *
     * This method is deliberately fail-safe: if writing the audit log fails for
     * any reason it is caught and swallowed so that the business operation is
     * not rolled back due to a logging problem.
     */
    public static function log(
        string   $action,
        ?int     $userId      = null,
        ?string  $entityType  = null,
        ?string  $entityId    = null,
        mixed    $oldData     = null,
        mixed    $newData     = null,
        ?Request $request     = null,
    ): void {
        try {
            AuditLog::create([
                'user_id'     => $userId,
                'action'      => $action,
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
                'old_data'    => $oldData,
                'new_data'    => $newData,
                'ip_address'  => $request?->ip(),
                'user_agent'  => $request?->userAgent(),
            ]);
        } catch (Throwable) {
            // Audit failure must never break business logic.
        }
    }
}

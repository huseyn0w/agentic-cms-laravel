<?php

namespace App\Services\CPanel;

use App\Repositories\CPanelAuditLogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

/**
 * Domain service for the security audit log. Records events (with the request
 * IP / user-agent) and reads the trail for the admin Security screen. All
 * persistence goes through CPanelAuditLogRepository.
 */
class AuditLogService
{
    public function __construct(private CPanelAuditLogRepository $repo) {}

    /**
     * Append an audit entry. Never throws into the caller — audit logging must
     * not break the flow it observes (e.g. a login).
     */
    public function record(string $action, ?string $description = null, ?int $userId = null, ?string $actor = null): void
    {
        try {
            $request = request();

            $this->repo->record([
                'action' => $action,
                'description' => $description,
                'user_id' => $userId,
                'actor' => $actor,
                'ip' => $request?->ip(),
                'user_agent' => substr((string) $request?->userAgent(), 0, 255) ?: null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Audit log write failed', ['action' => $action, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Newest-first page of audit rows, optionally filtered by action.
     */
    public function list(?string $action, int $perPage = 30): LengthAwarePaginator
    {
        return $this->repo->paginateFiltered($action, $perPage);
    }
}

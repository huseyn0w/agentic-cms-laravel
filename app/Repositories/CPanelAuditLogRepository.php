<?php

namespace App\Repositories;

use App\Http\Models\CPanel\CPanelAuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Data access for the security audit log. All Eloquent query building for the
 * audit trail lives here (controllers/services never touch the ORM).
 */
class CPanelAuditLogRepository extends BaseRepository
{
    public function __construct(CPanelAuditLog $model)
    {
        parent::__construct();
        $this->model = $model;
    }

    /**
     * Append one audit row.
     *
     * @param  array<string, mixed>  $data
     */
    public function record(array $data): CPanelAuditLog
    {
        return $this->model->create($data);
    }

    /**
     * Newest-first page of audit rows, optionally filtered by action, with the
     * related user eager-loaded.
     */
    public function paginateFiltered(?string $action, int $perPage = 30): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->when($action, fn ($q) => $q->where('action', $action))
            ->with('user:id,username')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}

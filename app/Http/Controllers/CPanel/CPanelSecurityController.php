<?php

namespace App\Http\Controllers\CPanel;

use App\Services\CPanel\AuditLogService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Security screen (read-only for this slice): the authentication audit trail.
 * Gated by manage_general_settings on the route group.
 */
class CPanelSecurityController extends CPanelBaseController
{
    private const ACTIONS = ['login', 'login_failed', 'logout', 'lockout'];

    public function __construct(private AuditLogService $audit)
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $filter = $request->query('action');
        $filter = in_array($filter, self::ACTIONS, true) ? $filter : null;

        $log = $this->audit->list($filter, 30);
        $log->getCollection()->transform(fn ($row) => [
            'id' => $row->id,
            'action' => $row->action,
            'description' => $row->description,
            'actor' => $row->user->username ?? $row->actor,
            'ip' => $row->ip,
            'when' => optional($row->created_at)->format('d.m.Y H:i'),
        ]);

        return Inertia::render('cpanel/security/Index', [
            'audit_log' => $log,
            'filter' => $filter,
            'actions' => self::ACTIONS,
        ]);
    }
}

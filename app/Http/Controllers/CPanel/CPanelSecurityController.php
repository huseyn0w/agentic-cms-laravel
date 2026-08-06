<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Requests\ValidateSecuritySettings;
use App\Services\CPanel\AuditLogService;
use App\Services\CPanel\SecuritySettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Security screen: the authentication audit trail plus the login-protection
 * settings form. Gated by manage_general_settings on the route group.
 */
class CPanelSecurityController extends CPanelBaseController
{
    private const ACTIONS = ['login', 'login_failed', 'logout', 'lockout'];

    public function __construct(
        private AuditLogService $audit,
        private SecuritySettingsService $settings,
    ) {
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

        $security = $this->settings->currentOrNew();

        return Inertia::render('cpanel/security/Index', [
            'audit_log' => $log,
            'filter' => $filter,
            'actions' => self::ACTIONS,
            'security_settings' => [
                'login_throttle_enabled' => (bool) ($security->login_throttle_enabled ?? true),
                'login_max_attempts' => (int) ($security->login_max_attempts ?? 5),
                'login_decay_minutes' => (int) ($security->login_decay_minutes ?? 1),
                'login_block_enabled' => (bool) ($security->login_block_enabled ?? false),
                'login_block_threshold' => (int) ($security->login_block_threshold ?? 10),
                'login_block_minutes' => (int) ($security->login_block_minutes ?? 60),
            ],
        ]);
    }

    public function updateSettings(ValidateSecuritySettings $request)
    {
        $this->settings->save($request);

        return back()->with('success', __('cpanel/security.settings_saved'));
    }
}

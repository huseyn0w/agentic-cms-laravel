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
    private const ACTIONS = ['login', 'login_failed', 'logout', 'lockout', '2fa_enabled', '2fa_disabled', '2fa_failed'];

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
                'require_2fa_for_admins' => (bool) ($security->require_2fa_for_admins ?? false),
                'password_min_length' => (int) ($security->password_min_length ?? 8),
                'password_require_mixed_case' => (bool) ($security->password_require_mixed_case ?? false),
                'password_require_numbers' => (bool) ($security->password_require_numbers ?? false),
                'password_require_symbols' => (bool) ($security->password_require_symbols ?? false),
                'password_check_hibp' => (bool) ($security->password_check_hibp ?? false),
                'hsts_enabled' => (bool) ($security->hsts_enabled ?? false),
                'hsts_max_age' => (int) ($security->hsts_max_age ?? 15552000),
                'csp' => (string) ($security->csp ?? ''),
                'csp_report_only' => (bool) ($security->csp_report_only ?? false),
                'admin_ip_allowlist' => (string) ($security->admin_ip_allowlist ?? ''),
                'site_lockdown_enabled' => (bool) ($security->site_lockdown_enabled ?? false),
                'password_history_count' => (int) ($security->password_history_count ?? 0),
            ],
            'current_ip' => $request->ip(),
        ]);
    }

    public function updateSettings(ValidateSecuritySettings $request)
    {
        $this->settings->save($request);

        return back()->with('success', __('cpanel/security.settings_saved'));
    }
}

<?php

namespace App\Http\Controllers\CPanel;

use App\Services\CPanel\AuditLogService;
use App\Services\CPanel\CPanelSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Self-service management of the authenticated user's browser sessions. Every
 * action is scoped to the current user by the service/repository, so one user
 * can never revoke another's session. Lives under the myprofile route group
 * (auth + see_admin_panel).
 */
class CPanelSessionController extends CPanelBaseController
{
    public function __construct(
        private CPanelSessionService $sessions,
        private AuditLogService $audit,
    ) {
        parent::__construct();
    }

    /**
     * Revoke a single session of the current user. Deleting the row logs that
     * browser out on its next request.
     */
    public function revoke(Request $request, string $id): RedirectResponse
    {
        if ($this->sessions->revoke($this->user->id, $id) > 0) {
            $this->audit->record('session_revoked', null, $this->user->id, $this->user->username);
        }

        return back()->with('success', __('cpanel/sessions.revoked'));
    }

    /**
     * Revoke every other session of the current user (keeps this one). Guarded
     * by the current password, mirroring the 2FA disable flow.
     */
    public function logoutOthers(Request $request): RedirectResponse
    {
        $request->validate(['password' => 'required|current_password']);

        $this->sessions->revokeOthers($this->user->id, $request->session()->getId());
        $this->audit->record('session_revoked', 'others', $this->user->id, $this->user->username);

        return back()->with('success', __('cpanel/sessions.others_logged_out'));
    }
}

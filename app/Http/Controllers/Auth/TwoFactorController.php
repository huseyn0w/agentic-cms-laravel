<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\TwoFactorService;
use App\Services\CPanel\AuditLogService;
use App\Services\CPanel\CPanelUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Per-user 2FA enrollment (authenticated). The QR + secret and freshly
 * generated recovery codes are flashed to the session for a one-shot display on
 * the profile page (see CPanelUserController::editUser). The route group applies
 * the `auth` middleware.
 */
class TwoFactorController extends Controller
{
    public function __construct(
        private TwoFactorService $twoFactor,
        private CPanelUserService $users,
        private AuditLogService $audit,
    ) {}

    public function enable(Request $request): RedirectResponse
    {
        $user = $request->user();
        $secret = $this->twoFactor->generateSecret();
        $this->users->startTwoFactorEnrollment($user, $secret);

        $svg = $this->twoFactor->qrCodeSvg((string) config('app.name'), (string) $user->email, $secret);

        return back()->with('two_factor_setup', ['secret' => $secret, 'qr_svg' => $svg]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => 'required|string']);
        $user = $request->user();

        if (empty($user->two_factor_secret) || ! $this->twoFactor->verify($user->two_factor_secret, $data['code'])) {
            throw ValidationException::withMessages([
                'code' => [trans('cpanel/twofactor.invalid_code')],
            ]);
        }

        $codes = $this->twoFactor->generateRecoveryCodes();
        $this->users->confirmTwoFactor($user, $codes);
        $this->audit->record('2fa_enabled', null, $user->id, $user->username);

        return back()->with('two_factor_recovery_codes', $codes);
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate(['password' => 'required|current_password']);
        $user = $request->user();

        $this->users->disableTwoFactor($user);
        $this->audit->record('2fa_disabled', null, $user->id, $user->username);

        return back()->with('status', trans('cpanel/twofactor.disabled'));
    }

    public function recoveryCodes(Request $request): RedirectResponse
    {
        $request->validate(['password' => 'required|current_password']);
        $user = $request->user();

        $codes = $this->twoFactor->generateRecoveryCodes();
        $this->users->replaceTwoFactorRecoveryCodes($user, $codes);

        return back()->with('two_factor_recovery_codes', $codes);
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\ThrottlesLogins;
use App\Http\Controllers\Controller;
use App\Http\Models\User;
use App\Services\Auth\TwoFactorService;
use App\Services\CPanel\AuditLogService;
use App\Services\CPanel\CPanelUserService;
use Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Second factor of the interactive login. Runs while the user has passed the
 * password step but is NOT yet authenticated (the pending id lives in the
 * session). Reuses the shared login throttle keyed on the pending user's
 * email + IP, so failed password and failed challenge attempts accumulate.
 */
class TwoFactorChallengeController extends Controller
{
    use ThrottlesLogins;

    public function __construct(
        private TwoFactorService $twoFactor,
        private CPanelUserService $users,
        private AuditLogService $audit,
    ) {}

    public function show(Request $request): InertiaResponse|RedirectResponse
    {
        if (! $request->session()->has('two_factor.user_id')) {
            return redirect('/login');
        }

        return Inertia::render('auth/TwoFactorChallenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('two_factor.user_id');
        if (! $userId) {
            return redirect('/login');
        }

        /** @var User $user */
        $user = User::findOrFail($userId);

        // Reuse the login throttle: merge the email so throttleKey matches the
        // password step's key.
        $request->merge(['email' => $user->email]);
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            $this->sendLockoutResponse($request);
        }

        $code = trim((string) $request->input('code'));
        $ok = $this->twoFactor->verify((string) $user->two_factor_secret, $code)
            || $this->users->consumeTwoFactorRecoveryCode($user, $code);

        if (! $ok) {
            $this->incrementLoginAttempts($request);
            $this->audit->record('2fa_failed', null, $user->id, $user->username);

            throw ValidationException::withMessages([
                'code' => [trans('cpanel/twofactor.invalid_code')],
            ]);
        }

        $remember = (bool) $request->session()->get('two_factor.remember');
        Auth::login($user, $remember);                       // fires Login -> audit 'login'
        $request->session()->regenerate();
        $this->clearLoginAttempts($request);
        $request->session()->forget(['two_factor.user_id', 'two_factor.remember']);

        return redirect()->intended('/');
    }

    /** Throttle key is email+IP (same as LoginController). */
    public function username(): string
    {
        return 'email';
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Models\User;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\Auth\PasswordResetService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ResetPasswordController extends Controller
{
    protected $redirectTo = '/';

    public function __construct(private PasswordResetService $passwordReset)
    {
        $this->middleware('guest');
    }

    public function showResetForm(Request $request, string $token): InertiaResponse
    {
        return Inertia::render('auth/ResetPassword', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    public function reset(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $this->setUserPassword($user, $password);
                $user->setRememberToken(Str::random(60));
                $user->save();

                event(new PasswordReset($user));

                Auth::login($user);
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect($this->redirectTo)->with('status', trans($status))
            : back()->withInput($request->only('email'))->withErrors(['email' => trans($status)]);
    }

    /**
     * Assign the new password as PLAINTEXT — User's setPasswordAttribute mutator
     * hashes once. Calling Hash::make() here would double-hash and break login.
     */
    protected function setUserPassword(User $user, string $password): void
    {
        $this->passwordReset->setPassword($user, $password);
    }
}

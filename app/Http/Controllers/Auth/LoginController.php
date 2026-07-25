<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\ThrottlesLogins;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\SocialAuthService;
use App\Services\Auth\SocialEmailNotVerifiedException;
use Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class LoginController extends Controller
{
    use ThrottlesLogins;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    public function __construct(private SocialAuthService $socialAuth)
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm(): InertiaResponse
    {
        return Inertia::render('auth/Login', [
            'status' => session('status'),
            'canResetPassword' => Route::has('password.request'),
            'membershipEnabled' => (bool) get_general_settings('membership'),
        ]);
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            $this->sendLockoutResponse($request);
        }

        if (Auth::attempt($this->credentials($request), $request->boolean('remember'))) {
            $request->session()->regenerate();
            $this->clearLoginAttempts($request);

            return redirect()->intended($this->redirectTo);
        }

        $this->incrementLoginAttempts($request);

        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * The field the login form submits and the throttle key are both `email`.
     * credentials() still decides email-vs-username at attempt time.
     */
    public function username(): string
    {
        return 'email';
    }

    /**
     * Redirect the user to the OAuth provider's authentication page.
     *
     * @return SymfonyRedirectResponse
     */
    public function redirectToProvider($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Obtain the user information from the OAuth provider.
     *
     * @return RedirectResponse
     */
    public function handleProviderCallback($provider)
    {
        $socialUser = Socialite::driver($provider)->user();

        try {
            $authUser = $this->socialAuth->findOrLink($socialUser, $provider);
        } catch (SocialEmailNotVerifiedException $e) {
            return redirect()->route('login')
                ->with('status', trans('default/auth.social_email_unverified'));
        }

        if ($authUser) {
            Auth::login($authUser, true);

            return redirect($this->redirectTo);
        }

        // Creating a brand-new account via a provider is a signup, so it is
        // gated by the same membership toggle as the register form. Linking an
        // existing account above is a login and stays allowed.
        if (! get_general_settings('membership')) {
            return redirect()->route('login')
                ->with('status', trans('default/auth.registration_disabled'));
        }

        $validator = $this->socialAuth->validateNew($socialUser);

        if ($validator !== true) {
            return redirect('login')
                ->withErrors($validator)
                ->withInput();
        }

        $registered_user = $this->socialAuth->create($socialUser, $provider);
        Auth::login($registered_user, true);

        return redirect($this->redirectTo);
    }

    protected function credentials(Request $request)
    {
        $field = filter_var($request->get($this->username()), FILTER_VALIDATE_EMAIL)
            ? $this->username()
            : 'username';

        return [
            $field => $request->get($this->username()),
            'password' => $request->password,
        ];
    }
}

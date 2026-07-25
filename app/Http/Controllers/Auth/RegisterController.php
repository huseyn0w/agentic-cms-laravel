<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\UserRegistrationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class RegisterController extends Controller
{
    protected $redirectTo = '/';

    public function __construct(private UserRegistrationService $registrar)
    {
        $this->middleware('guest');
        $this->middleware('registration_enabled');
    }

    public function showRegistrationForm(): InertiaResponse
    {
        return Inertia::render('auth/Register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = $this->registrar->register($request->validated());

        event(new Registered($user));

        Auth::login($user);

        return redirect($this->redirectTo);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\FrontEndUserRequest;
use App\Services\Front\ProfileService;
use App\Services\Front\PublicShell;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UserController extends BaseController
{
    public function __construct(ProfileService $service, private PublicShell $shell)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function yourProfile()
    {
        $username = get_logged_user_username();
        $user = $this->service->byUsername($username);

        return view('default.users.yourprofile', compact('user'));
    }

    public function update(FrontEndUserRequest $request)
    {
        $user_id = get_logged_user_id();
        $this->service->update($user_id, $request);

        return back()->with('message', ' ');
    }

    public function password()
    {
        return view('default.users.change_password');
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $result = $this->service->changePassword($request);

        if (! $result) {
            return redirect()->back()->withErrors(trans('cpanel/controller.password_match'));
        }

        return back()->with('message', ' ');
    }

    public function show($username)
    {
        $user = $this->service->byUsername($username);

        $displayName = trim(($user->name ?? '').' '.($user->surname ?? '')) ?: $user->username;

        $socials = collect([
            'Facebook' => $user->facebook_url,
            'Google' => $user->google_url,
            'Twitter' => $user->twitter_url,
            'Instagram' => $user->instagram_url,
            'LinkedIn' => $user->linkedin_url,
            'Xing' => $user->xing_url,
        ])->filter()->map(fn ($url, $label) => ['label' => $label, 'url' => $url])->values()->all();

        return Inertia::render('public/Profile', [
            'shell' => $this->shell->build(),
            'profile' => [
                'displayName' => $displayName,
                'username' => $user->username,
                'avatar' => image_src($user->avatar, true),
                'role' => $user->role->name ?? null,
                'gender' => $user->gender ?: null,
                'aboutMe' => $user->about_me ?: null,
                'email' => $user->email,
                'country' => $user->country ?: null,
                'city' => $user->city ?: null,
                'socials' => $socials,
                'isOwnProfile' => Auth::check() && Auth::user()->username === $user->username,
                'editUrl' => route('get_user_info'),
            ],
        ])
            ->rootView('app-public')
            ->withViewData(['user' => $user]);
    }
}

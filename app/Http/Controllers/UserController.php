<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\FrontEndUserRequest;
use App\Services\Front\ProfileService;
use App\Services\Front\PublicShell;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends BaseController
{
    public function __construct(ProfileService $service, private PublicShell $shell)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function yourProfile(): Response
    {
        $username = get_logged_user_username();
        $user = $this->service->byUsername($username);

        return Inertia::render('public/ProfileEdit', [
            'shell' => $this->shell->build(),
            'title' => __('default/profile.profile'),
            'crumbs' => $this->noindexCrumbs(__('default/profile.edit_profile')),
            'action' => route('update_user_info'),
            'csrfToken' => csrf_token(),
            'changePasswordUrl' => route('get_change_password_interface'),
            'avatar' => image_src($user->avatar, true),
            'countries' => array_values(array_map(fn ($c) => $c['name'], get_countries_array())),
            'profile' => [
                'username' => $user->username,
                'email' => $user->email,
                'name' => $user->name,
                'surname' => $user->surname,
                'country' => $user->country,
                'city' => $user->city,
                'about_me' => $user->about_me,
                'gender' => $user->gender,
                'facebook_url' => $user->facebook_url,
                'google_url' => $user->google_url,
                'twitter_url' => $user->twitter_url,
                'instagram_url' => $user->instagram_url,
                'linkedin_url' => $user->linkedin_url,
                'xing_url' => $user->xing_url,
            ],
        ])
            ->rootView('app-public')
            ->withViewData(['data' => $this->noindexEntity(__('default/profile.edit_profile'))]);
    }

    public function update(FrontEndUserRequest $request)
    {
        $user_id = get_logged_user_id();
        $this->service->update($user_id, $request);

        return back()->with('success', __('default/profile.user_updated'));
    }

    public function password(): Response
    {
        return Inertia::render('public/ChangePassword', [
            'shell' => $this->shell->build(),
            'title' => __('default/change_password.headline'),
            'crumbs' => [
                ['label' => get_general_settings('website_name') ?: config('app.name'), 'url' => rtrim(config('app.url'), '/')],
                ['label' => __('default/change_password.edit_profile'), 'url' => route('get_user_info')],
                ['label' => __('default/change_password.change_password'), 'url' => null],
            ],
            'action' => route('change_password_action'),
            'csrfToken' => csrf_token(),
            'captchaHtml' => app('captcha')->render(),
        ])
            ->rootView('app-public')
            ->withViewData(['data' => $this->noindexEntity(__('default/change_password.change_password'))]);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $result = $this->service->changePassword($request);

        if (! $result) {
            return redirect()->back()->withErrors(trans('cpanel/controller.password_match'));
        }

        return back()->with('success', __('default/change_password.password_updated'));
    }

    /** Home -> current-screen breadcrumb, matching the Blade banner. */
    private function noindexCrumbs(string $title): array
    {
        return [
            ['label' => get_general_settings('website_name') ?: config('app.name'), 'url' => rtrim(config('app.url'), '/')],
            ['label' => $title, 'url' => null],
        ];
    }

    /** A synthetic, noindex SEO entity for the authenticated self-service screens. */
    private function noindexEntity(string $title): object
    {
        return (object) [
            'title' => $title,
            'meta_description' => null,
            'meta_keywords' => null,
            'canonical_url' => null,
            'meta_noindex' => true,
            'thumbnail' => null,
        ];
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

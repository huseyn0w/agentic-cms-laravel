<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Requests\UserListRequest;
use App\Http\Requests\ValidateUserSettings;
use App\Services\CPanel\CPanelUserService;
use Inertia\Inertia;

class CPanelUserController extends CPanelBaseController
{
    private $user_roles;

    private $countries;

    public function __construct(CPanelUserService $service)
    {
        parent::__construct();
        $this->service = $service;
        $this->user_roles = get_user_roles();
        $this->countries = get_countries_array();
    }

    public function index()
    {
        $users_list = $this->service->list($this->per_page);

        $users_list->getCollection()->transform(fn ($u) => [
            'id' => $u->id,
            'username' => $u->username,
            'email' => $u->email,
            'name' => $u->name,
            'surname' => $u->surname,
            'country' => $u->country,
            'city' => $u->city,
            'role' => $u->role?->name,
        ]);

        return Inertia::render('cpanel/users/List', [
            'users_list' => $users_list,
        ]);
    }

    public function editUser($id = null)
    {
        $id = $id ?? $this->user->id;

        $user = $this->service->getById($id);

        return Inertia::render('cpanel/users/Form', [
            'entity' => $this->userEntity($user),
            'countries' => $this->countryOptions(),
            'user_roles' => $this->roleOptions(),
        ]);
    }

    public function updateUser($id, ValidateUserSettings $request)
    {
        parent::update($id, $request);

        return back()->with('success', __('cpanel/users.updated_success'));
    }

    public function multipleDelete(UserListRequest $request)
    {
        $result = $this->service->delete($request->users);

        return back()->with('message', $result);
    }

    public function addUser()
    {
        return Inertia::render('cpanel/users/Form', [
            'entity' => null,
            'countries' => $this->countryOptions(),
            'user_roles' => $this->roleOptions(),
        ]);
    }

    public function createUser(ValidateUserSettings $request)
    {
        parent::create($request);

        return redirect()->route('cpanel_all_users_list')->with('success', __('cpanel/users.user_added'));
    }

    /**
     * Shape a User model into the flat prop the Inertia Form consumes.
     * Nullable DB columns collapse to '' so the React form never binds null.
     */
    private function userEntity($user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'name' => $user->name ?? '',
            'surname' => $user->surname ?? '',
            'country' => $user->country ?? '',
            'city' => $user->city ?? '',
            'about_me' => $user->about_me ?? '',
            'facebook_url' => $user->facebook_url ?? '',
            'twitter_url' => $user->twitter_url ?? '',
            'instagram_url' => $user->instagram_url ?? '',
            'google_url' => $user->google_url ?? '',
            'linkedin_url' => $user->linkedin_url ?? '',
            'xing_url' => $user->xing_url ?? '',
            'role_id' => $user->role_id,
            'gender' => $user->gender ?? '',
            'avatar' => $user->avatar ?? '',
        ];
    }

    private function countryOptions(): array
    {
        return collect($this->countries)
            ->map(fn ($c) => ['name' => $c['name']])
            ->values()
            ->all();
    }

    private function roleOptions(): array
    {
        return collect($this->user_roles)
            ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])
            ->values()
            ->all();
    }
}

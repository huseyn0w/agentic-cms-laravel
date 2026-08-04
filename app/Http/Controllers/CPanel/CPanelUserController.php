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

        return view('cpanel.users.profile', ['user' => $user, 'countries' => $this->countries, 'user_roles' => $this->user_roles]);
    }

    public function updateUser($id, ValidateUserSettings $request)
    {
        return parent::update($id, $request);

    }

    public function multipleDelete(UserListRequest $request)
    {
        $result = $this->service->delete($request->users);

        return back()->with('message', $result);
    }

    public function addUser()
    {
        return view('cpanel.users.new_user', ['countries' => $this->countries, 'user_roles' => $this->user_roles]);
    }

    public function createUser(ValidateUserSettings $request)
    {
        parent::create($request);

        return redirect()->route('cpanel_all_users_list')->with('user_added', ' ');
    }
}

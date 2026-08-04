<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Requests\ValidateUserRoles;
use App\Services\CPanel\CPanelRoleService;
use Inertia\Inertia;

class CPanelRoleController extends CPanelBaseController
{
    private $user_roles;

    private $countries;

    private $role_permissions;

    public function __construct(CPanelRoleService $service)
    {
        parent::__construct();
        $this->service = $service;
        $this->user_roles = get_user_roles();
        $this->countries = get_countries_array();
        $this->role_permissions = get_user_role_permissions();
    }

    public function index()
    {
        $roles_list = $this->service->list($this->per_page);

        $roles_list->getCollection()->transform(fn ($r) => [
            'id' => $r->id,
            'name' => $r->name,
        ]);

        return Inertia::render('cpanel/roles/List', [
            'roles_list' => $roles_list,
        ]);
    }

    public function addRole()
    {
        return Inertia::render('cpanel/roles/Form', [
            'entity' => null,
            'permission_options' => $this->permissionOptions(),
        ]);
    }

    public function createRole(ValidateUserRoles $request)
    {
        parent::create($request);

        return redirect()->route('cpanel_user_roles')->with('success', __('cpanel/roles.role_added'));
    }

    public function editRole($id)
    {
        parent::edit($id);

        return Inertia::render('cpanel/roles/Form', [
            'entity' => $this->roleEntity($this->result),
            'permission_options' => $this->permissionOptions(),
        ]);
    }

    public function updateRole($id, ValidateUserRoles $request)
    {
        parent::update($id, $request);

        return back()->with('success', __('cpanel/roles.role_updated'));
    }

    public function deleteRole($id)
    {
        $this->service->delete($id);

        return back()->with('success', __('cpanel/roles.js_delete'));
    }

    /**
     * Shape a UserRoles model for the Inertia form: the flat name plus the list
     * of permission names currently enabled (value === 1 in the JSON map).
     */
    private function roleEntity($role): array
    {
        $map = json_decode($role->permissions, true) ?: [];

        $enabled = collect($map)
            ->filter(fn ($v) => (int) $v === 1)
            ->keys()
            ->values()
            ->all();

        return [
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $enabled,
        ];
    }

    private function permissionOptions(): array
    {
        return collect($this->role_permissions)
            ->map(fn ($p) => ['name' => $p->name, 'label' => str_replace('_', ' ', $p->name)])
            ->values()
            ->all();
    }
}

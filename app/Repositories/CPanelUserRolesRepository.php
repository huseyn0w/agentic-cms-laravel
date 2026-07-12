<?php

/**
 * AgenticCms-Laravel
 * File: CPanelUserRepository.phpCreated by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 09.08.2019
 */

namespace App\Repositories;

use App\Http\Models\UserRoles;
use Illuminate\Foundation\Http\FormRequest;

class CPanelUserRolesRepository extends BaseRepository
{
    public function __construct(UserRoles $model)
    {
        parent::__construct();
        $this->model = $model;
    }

    public function create($request)
    {
        return parent::create($this->prepare_role_data($request));
    }

    public function update($id, $request)
    {
        return parent::update($id, $this->prepare_role_data($request));
    }

    /**
     * Build an explicit, whitelisted attribute array for a role. The full
     * permission map is rebuilt server-side (defaulting every permission to 0)
     * and only the submitted permission names are flipped to 1, so the stored
     * JSON can never contain attacker-defined permission keys.
     *
     * @param  FormRequest  $request
     * @return array<string, mixed>
     */
    private function prepare_role_data($request): array
    {
        $validated = $request->validated();

        $permissions = [];

        foreach (get_user_role_permissions() as $permission) {
            $permissions[$permission->name] = 0;
        }

        $submitted = $validated['permissions'] ?? [];

        if (is_array($submitted)) {
            foreach ($submitted as $permission_name) {
                // Only flip permissions that actually exist.
                if (array_key_exists($permission_name, $permissions)) {
                    $permissions[$permission_name] = 1;
                }
            }
        }

        return [
            'name' => $validated['name'] ?? null,
            'permissions' => json_encode($permissions),
        ];
    }
}

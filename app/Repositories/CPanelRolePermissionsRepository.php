<?php

/**
 * AgenticCms-Laravel
 * File: CPanelUserRepository.phpCreated by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 09.08.2019
 */

namespace App\Repositories;

use App\Http\Models\UserPermissions;

class CPanelRolePermissionsRepository extends BaseRepository
{
    public function __construct(UserPermissions $model)
    {
        parent::__construct();
        $this->model = $model;
    }
}

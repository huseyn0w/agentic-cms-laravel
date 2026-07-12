<?php

/**
 * AgenticCms-Laravel
 * File: CPanelGeneralSettingRepository.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 25.07.2019
 */

namespace App\Repositories;

use App\Http\Models\CPanel\CPanelSiteOptions;

class CPanelSiteOptionsRepository extends BaseRepository
{
    public function __construct(CPanelSiteOptions $model)
    {
        parent::__construct();
        $this->model = $model;
    }
}

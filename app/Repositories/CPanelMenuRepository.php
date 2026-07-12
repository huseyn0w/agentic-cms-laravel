<?php

/**
 * AgenticCms-Laravel
 * File: CPanelUserRepository.phpCreated by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 09.08.2019
 */

namespace App\Repositories;

use App\Http\Models\Menu;
use App\Http\Models\MenuTranslation;

class CPanelMenuRepository extends BaseRepository
{
    protected $main_table = 'menus';

    protected $translated_table = 'menu_translations';

    protected $translated_table_join_column = 'menu_id';

    protected $select_fields = [
        'id',
        'author_id',
        'title',
        'slug',
    ];

    public function __construct(Menu $model)
    {
        parent::__construct();
        $this->model = $model;
        $this->translated_model = new MenuTranslation;
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('user_permissions')->insertOrIgnore(
            [
                ['name' => 'manage_general_settings'],
                ['name' => 'manage_users'],
                ['name' => 'manage_user_roles'],
                ['name' => 'manage_pages'],
                ['name' => 'manage_posts'],
                ['name' => 'manage_services'],
                ['name' => 'manage_post_categories'],
                ['name' => 'manage_menus'],
                ['name' => 'manage_comments'],
                ['name' => 'manage_media'],
                ['name' => 'manage_newsletter'],
                ['name' => 'manage_messages'],
                ['name' => 'manage_content'],
                ['name' => 'manage_updates'],
                ['name' => 'see_admin_panel'],
            ]
        );
    }
}

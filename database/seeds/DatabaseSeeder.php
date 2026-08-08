<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UsersTableSeeder::class);
        $this->call([
            UserPermissionsSeeder::class,
            UserRolesSeeder::class,
            CPanelGeneralSettingsSeeder::class,
            UsersTableSeeder::class,
            CPanelPagesSeeder::class,
            LegalPagesSeeder::class,
            CPanelCategorySeeder::class,
            CPanelPostsSeeder::class,
            CPanelPostCategorySeeder::class,
            CPanelServicesSeeder::class,
            CPanelMenusSeeder::class,
            SiteOptionsTableSeeder::class,
            SeoSettingsTableSeeder::class,
        ]);
    }
}

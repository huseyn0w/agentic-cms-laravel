<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CPanelGeneralSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('general_settings')->insertOrIgnore([
            'website_name' => 'AgenticCms-Laravel',
            'tagline' => 'Build your project on AgenticCms-Laravel and take an advantage of it',
            'contact_email' => 'thehuseyn0w@gmail.com',
            'posts_per_page' => 10,
            'comments_per_page' => 5,
            'membership' => '1',
            'email_verification' => '0',
            'active_template_name' => 'default',
        ]);
    }
}

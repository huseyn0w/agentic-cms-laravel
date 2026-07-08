<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SiteOptionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('site_options')->insertOrIgnore([
            [
                'logo_url' => env('APP_URL').'/filemanager/images/5db423b7ed176.png',
                'copyright' => 'Copyright ©2019 All rights reserved | This template is made with love by <a href="https://colorlib.com" target="_blank">Colorlib</a>',
                'linkedin_url' => 'https://linkedin.com/in/huseyn0w',
                'github_url' => 'https://github.com/huseyn0w/cmstack-laravel',
            ],
        ]);
    }
}

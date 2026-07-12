<?php

namespace Database\Seeders;

use App\Http\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insertOrIgnore(
            [
                [
                    'name' => 'Elman',
                    'surname' => 'Hüseynov',
                    'email' => 'thehuseyn0w@gmail.com',
                    'username' => 'huseyn0w',
                    'city' => 'Baku',
                    'country' => 'Azerbaijan',
                    'role_id' => 1,
                    'gender' => 'male',
                    'avatar' => env('APP_URL').'/filemanager/images/5dbb536d16ce8.jpg',
                    'about_me' => 'Founder of CMS AgenticCms-Laravel',
                    'linkedin_url' => 'https://linkedin.com/in/huseyn0w',
                    'password' => bcrypt('elman123'),
                    'email_verified_at' => now(),
                ],
                [
                    'name' => 'Admin',
                    'surname' => '',
                    'email' => 'admin@ehuseynov.com',
                    'username' => 'admin',
                    'city' => '',
                    'country' => '',
                    'role_id' => 1,
                    'gender' => 'male',
                    'avatar' => '',
                    'about_me' => 'ADMIN of CMS AgenticCms-Laravel',
                    'linkedin_url' => '',
                    'password' => bcrypt('agentic-cmsadmin123'),
                    'email_verified_at' => now(),
                ],
            ]
        );

        User::factory()->count(30)->create();
    }
}

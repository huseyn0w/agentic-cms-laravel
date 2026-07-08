<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CPanelPostCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Links each demo post to its category from the canonical dataset
     * (post.categorySlug). Announcements gets Introducing + Manage-with-AI,
     * Guides gets SEO + Build-a-theme, Engineering gets Plugins + Comments.
     *
     * @return void
     */
    public function run()
    {
        $rows = [];
        foreach (DemoContent::posts() as $post) {
            $rows[] = [
                'category_id' => DemoContent::CATEGORY_IDS[$post['categorySlug']],
                'post_id' => $post['id'],
            ];
        }

        DB::table('category_post')->insertOrIgnore($rows);
    }
}

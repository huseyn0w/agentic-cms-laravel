<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CPanelCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds the three demo categories (Announcements, Guides, Engineering) from
     * the canonical trilingual dataset, one category_translations row per locale
     * (en/de/ru). Slugs are shared across locales. Owned by admin (author id 1).
     *
     * @return void
     */
    public function run()
    {
        $categoryRows = [];
        foreach (DemoContent::CATEGORY_IDS as $id) {
            $categoryRows[] = ['id' => $id];
        }
        DB::table('categories')->insertOrIgnore($categoryRows);

        $translationRows = [];
        foreach (DemoContent::categories() as $category) {
            $id = DemoContent::CATEGORY_IDS[$category['slug']];
            foreach (DemoContent::LOCALES as $locale) {
                $translationRows[] = [
                    'category_id' => $id,
                    'author_id' => 1,
                    'locale' => $locale,
                    'title' => $category['name'][$locale],
                    'slug' => $category['slug'],
                    'description' => $category['description'][$locale],
                    'meta_description' => $category['description'][$locale],
                    'meta_keywords' => $category['slug'],
                ];
            }
        }
        DB::table('category_translations')->insertOrIgnore($translationRows);
    }
}

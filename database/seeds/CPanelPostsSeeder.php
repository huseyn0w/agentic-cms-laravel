<?php

namespace Database\Seeders;

use App\Http\Models\Post;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CPanelPostsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds the six demo posts and six demo tags from the canonical trilingual
     * dataset. Every post gets a post_translations row per locale (en/de/ru),
     * all PUBLISHED (status = Post::STATUS_PUBLISHED) and owned by admin
     * (author id 1). Tags get a tag_translations row per locale. Slugs are
     * shared across locales. Post -> tag pivots are written here; post ->
     * category pivots live in CPanelPostCategorySeeder.
     *
     * @return void
     */
    public function run()
    {
        $now = Carbon::now();
        $thumbnail = env('APP_URL').'/filemanager/images/5dbb5723de46f.jpg';

        // --- Tags (6) --------------------------------------------------------
        $tagRows = [];
        foreach (DemoContent::TAG_IDS as $id) {
            $tagRows[] = ['id' => $id];
        }
        DB::table('tags')->insertOrIgnore($tagRows);

        $tagTranslationRows = [];
        foreach (DemoContent::tags() as $tag) {
            $id = DemoContent::TAG_IDS[$tag['slug']];
            foreach (DemoContent::LOCALES as $locale) {
                $tagTranslationRows[] = [
                    'tag_id' => $id,
                    'locale' => $locale,
                    'name' => $tag['name'][$locale],
                    'slug' => $tag['slug'],
                ];
            }
        }
        DB::table('tag_translations')->insertOrIgnore($tagTranslationRows);

        // --- Posts (6) -------------------------------------------------------
        $postRows = [];
        $translationRows = [];
        $tagPivotRows = [];

        foreach (DemoContent::posts() as $post) {
            $postId = $post['id'];
            $postRows[] = ['id' => $postId];

            foreach (DemoContent::LOCALES as $locale) {
                $translationRows[] = [
                    'post_id' => $postId,
                    'author_id' => 1,
                    'locale' => $locale,
                    'title' => $post['title'][$locale],
                    'slug' => $post['slug'],
                    'status' => Post::STATUS_PUBLISHED,
                    'preview' => $post['excerpt'][$locale],
                    'content' => $post['content'][$locale],
                    'meta_keywords' => implode(', ', $post['tagSlugs']),
                    'meta_description' => $post['excerpt'][$locale],
                    'thumbnail' => $thumbnail,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach ($post['tagSlugs'] as $tagSlug) {
                $tagPivotRows[] = ['post_id' => $postId, 'tag_id' => DemoContent::TAG_IDS[$tagSlug]];
            }
        }

        DB::table('posts')->insertOrIgnore($postRows);
        DB::table('post_translations')->insertOrIgnore($translationRows);
        DB::table('post_tag')->insertOrIgnore($tagPivotRows);
    }
}

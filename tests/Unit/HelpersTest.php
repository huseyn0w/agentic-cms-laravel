<?php

namespace Tests\Unit;

use App\Http\Models\Category;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit coverage for the pure-ish helpers in bootstrap/agentic-cms-laravel-helpers.php:
 * language helpers, slug/translation list helpers and category post counting.
 */
class HelpersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_get_lang_prefixes_returns_configured_languages(): void
    {
        $prefixes = get_lang_prefixes();

        $this->assertContains('en', $prefixes);
        $this->assertContains('ru', $prefixes);
    }

    public function test_get_current_lang_defaults_to_app_locale(): void
    {
        \Session::forget('locale');
        $this->assertSame(app()->getLocale(), get_current_lang());
    }

    public function test_get_current_lang_honours_session_locale(): void
    {
        \Session::put('locale', 'ru');
        $this->assertSame('ru', get_current_lang());
        \Session::forget('locale');
    }

    public function test_set_current_lang_changes_app_locale(): void
    {
        set_current_lang('ru');
        $this->assertSame('ru', app()->getLocale());
        set_current_lang('en');
    }

    public function test_get_current_lang_prefix_is_null_for_default_locale(): void
    {
        \Session::put('locale', config('app.locale'));
        $this->assertNull(get_current_lang_prefix());
        \Session::forget('locale');
    }

    public function test_get_current_lang_prefix_appends_slash_for_non_default(): void
    {
        \Session::put('locale', 'ru');
        $this->assertSame('ru/', get_current_lang_prefix());
        \Session::forget('locale');
    }

    public function test_get_post_list_returns_qualified_join_results(): void
    {
        // Regression: this used to order by an ambiguous `id` and crash on the
        // posts/post_translations join under sqlite.
        $posts = get_post_list(['posts.id', 'post_translations.title', 'post_translations.slug']);

        $this->assertNotEmpty($posts);
        $this->assertNotNull($posts->first()->slug);
    }

    public function test_get_pages_list_returns_qualified_join_results(): void
    {
        $pages = get_pages_list(['pages.id', 'page_translations.title', 'page_translations.slug']);

        $this->assertNotEmpty($pages);
        $this->assertNotNull($pages->first()->slug);
    }

    public function test_get_post_categories_list_returns_translations_for_current_locale(): void
    {
        $categories = get_post_categories_list();

        $this->assertNotEmpty($categories);
    }

    public function test_get_category_posts_count_returns_zero_for_missing_category(): void
    {
        $this->assertSame(0, get_category_posts_count(999999));
    }

    public function test_get_category_posts_count_counts_attached_posts(): void
    {
        // Seeded category id 1 has at least the seeded post attached.
        $count = get_category_posts_count(1);

        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function test_get_entity_translation_links_excludes_current_locale(): void
    {
        \Session::put('locale', 'en');
        $links = get_entity_translation_links('posts', 1);

        // The current locale (en) must be skipped; only other languages remain.
        $this->assertArrayNotHasKey('English', $links);
        $this->assertArrayHasKey('Русский', $links);
        $this->assertStringContainsString('agentic-cms-laravel-admin/posts/1/ru', $links['Русский']);
        \Session::forget('locale');
    }
}

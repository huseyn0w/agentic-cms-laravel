<?php

namespace Tests\Feature\Front;

use App\Http\Models\PostTranslation;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class LanguageSwitchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_default_locale_post_resolves_despite_stale_session(): void
    {
        // Seeded post slug "introducing-the-cms" (en). A stale ru session must
        // not cause the en slug to be looked up in ru → 404.
        $this->withSession(['locale' => 'ru'])
            ->get('/posts/introducing-the-cms')
            ->assertStatus(200);
    }

    public function test_localized_post_route_does_not_redirect(): void
    {
        // The localized route renders directly (200), no 302 language redirect.
        $this->get('/en/posts/introducing-the-cms')->assertStatus(200);
    }

    public function test_self_referential_urls_on_a_non_default_locale_carry_the_prefix(): void
    {
        // On a ru post page, internal links (category/related) must include the
        // /ru/ prefix, otherwise clicking them drops to the default locale and
        // 404s. The default-locale page keeps un-prefixed URLs.
        $ru = PostTranslation::where('locale', 'ru')->first();
        $this->assertNotNull($ru, 'seeded ru post translation');

        $this->get('/ru/posts/'.$ru->slug)
            ->assertStatus(200)
            ->assertInertia(fn (AssertableInertia $p) => $p->where(
                'post.category',
                fn ($category) => $category === null || str_contains((string) ($category['url'] ?? ''), '/ru/category/')
            ));
    }
}

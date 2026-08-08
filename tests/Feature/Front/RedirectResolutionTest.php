<?php

namespace Tests\Feature\Front;

use App\Http\Models\Redirect;
use App\Services\RedirectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The ResolveRedirects middleware issues managed 301/302s for old URLs (WP
 * permalinks post-migration) before the front catch-all resolves, and leaves
 * unmapped paths untouched.
 */
class RedirectResolutionTest extends TestCase
{
    use RefreshDatabase;

    private function service(): RedirectService
    {
        return app(RedirectService::class);
    }

    public function test_a_mapped_old_url_301s_to_the_new_target(): void
    {
        $this->service()->save('/better-hiring/old-post/', '/blog/new-post', 301);

        $this->get('/better-hiring/old-post')
            ->assertStatus(301)
            ->assertRedirect('/blog/new-post');
    }

    public function test_a_trailing_slash_and_query_still_match(): void
    {
        $this->service()->save('/services/consulting', '/consultations', 301);

        // Normalization strips the trailing slash and the query string.
        $this->get('/services/consulting/?utm=1')
            ->assertStatus(301)
            ->assertRedirect('/consultations');
    }

    public function test_a_302_is_honored(): void
    {
        $this->service()->save('/temp', '/somewhere', 302);

        $this->get('/temp')->assertStatus(302)->assertRedirect('/somewhere');
    }

    public function test_an_unmapped_path_is_not_redirected(): void
    {
        // No redirect row → the request falls through to normal routing (404 for
        // a non-existent page, not a 301).
        $response = $this->get('/definitely-not-mapped-'.uniqid());
        $this->assertNotContains($response->getStatusCode(), [301, 302]);
    }

    public function test_a_self_redirect_loop_is_ignored(): void
    {
        // Source == target (after normalization) must not redirect.
        Redirect::create(['source_path' => '/loop', 'target' => '/loop/', 'status_code' => 301]);
        $this->service()->flushCache();

        $response = $this->get('/loop');
        $this->assertNotContains($response->getStatusCode(), [301, 302]);
    }

    public function test_admin_paths_are_never_redirected(): void
    {
        $this->service()->save('/agentic-cms-laravel-admin/anything', '/elsewhere', 301);

        // Admin prefix is skipped by the middleware, so it never issues the
        // managed 301 to /elsewhere — the request falls through to routing.
        $response = $this->get('/agentic-cms-laravel-admin/anything');
        $this->assertNotContains($response->getStatusCode(), [301, 302]);
    }

    public function test_hits_are_counted(): void
    {
        $this->service()->save('/counted', '/target', 301);

        $this->get('/counted');

        $this->assertSame(1, Redirect::where('source_path', '/counted')->first()->hits);
    }

    public function test_resolution_degrades_gracefully_when_the_table_is_absent(): void
    {
        // A fresh install (pre-migration) or a DB outage must not 500 the
        // request through the redirect middleware — the map degrades to empty
        // and the request falls through. This mirrors the /health liveness
        // contract, which has no DB dependency.
        $this->service()->flushCache();
        Schema::dropIfExists('redirects');

        $this->assertNull($this->service()->resolve('/anything'));
    }
}

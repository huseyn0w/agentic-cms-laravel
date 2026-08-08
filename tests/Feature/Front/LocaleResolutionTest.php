<?php

namespace Tests\Feature\Front;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class LocaleResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_front_default_locale_ignores_stale_session(): void
    {
        // A stale session locale must NOT bleed into an un-prefixed front URL:
        // the shared locale prop (built from get_current_lang at request time)
        // must be the default, not the stale session value.
        $this->withSession(['locale' => 'ru'])
            ->get('/')
            ->assertStatus(200)
            ->assertInertia(fn (AssertableInertia $p) => $p->where('locale.current', config('app.locale')));
    }

    public function test_front_locale_comes_from_url_prefix(): void
    {
        $this->get('/ru')
            ->assertStatus(200)
            ->assertInertia(fn (AssertableInertia $p) => $p->where('locale.current', 'ru'));
    }

    public function test_get_current_lang_reflects_app_locale(): void
    {
        app()->setLocale('ru');

        $this->assertSame('ru', get_current_lang());
    }
}

<?php

namespace Tests\Browser;

use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\Browser\Concerns\ReadsComputedStyle;
use Tests\DuskTestCase;

/**
 * e2e: admin authentication + the two admin-UI regressions we just fixed —
 * the login form must be styled (Tailwind, not raw Bootstrap markup) and the
 * dark sidebar's link text must be light/readable (not dark-on-dark).
 */
class AuthAndAdminTest extends DuskTestCase
{
    use DatabaseMigrations;
    use ReadsComputedStyle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_login_page_is_styled_and_admin_can_sign_in(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->assertSee('Login')
                ->assertVisible('form[action*="/login"] button[type=submit]')
                ->assertPresent('input[name=email]')
                ->assertPresent('input[name=password]');

            // STYLE: the submit button must be the brand red — if the page were
            // unstyled (the bug), this would be a default/transparent background.
            $bg = $browser->script(
                "return getComputedStyle(document.querySelector('form[action*=\"/login\"] button[type=submit]')).backgroundColor"
            )[0];
            $this->assertTrue($this->isReddish($bg), "Login button is not brand-styled, got: {$bg}");

            // FUNCTION: sign in with the seeded admin credentials.
            $browser->type('email', 'admin')
                ->type('password', 'agentic-cmsadmin123')
                ->click('form[action*="/login"] button[type=submit]')
                ->waitUntilMissing('input[name=password]');

            // Reaching the panel proves authentication succeeded.
            $browser->visit('/agentic-cms-laravel-admin')
                ->waitForText('Dashboard')
                ->assertSee('Dashboard');
        });
    }

    public function test_admin_sidebar_text_is_readable_on_the_dark_rail(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/agentic-cms-laravel-admin')
                ->waitFor('aside')
                ->assertSee('Pages')
                ->assertSee('Users');

            // STYLE: a sidebar link's text colour must be light (high brightness)
            // so it is readable on the dark rail. The fixed bug rendered it
            // dark-on-dark (it inherited the near-black body colour).
            $color = $browser->script(
                "var a=[...document.querySelectorAll('aside a')].find(e=>e.textContent.trim()==='Users');".
                'return a?getComputedStyle(a).color:null'
            )[0];

            $this->assertNotNull($color, 'Sidebar "Users" link was not found');
            $this->assertGreaterThan(
                360,
                $this->brightness($color),
                "Sidebar link colour is too dark to read on the dark rail: {$color}"
            );
        });
    }
}

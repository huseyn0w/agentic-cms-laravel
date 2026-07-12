<?php

namespace Tests\Browser;

use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * e2e: the GEO settings admin page renders and works end-to-end — an admin
 * fills the form, saves, sees the success flash, and the entered data is then
 * reflected in the public /llms.txt (the whole point: machine-readable GEO).
 */
class GeoSettingsBrowserTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_fills_geo_settings_and_it_reaches_llms_txt(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/agentic-cms-laravel-admin/geo-settings')
                ->waitForText('GEO')
                ->assertPresent('select[name=business_type]')
                ->assertPresent('textarea[name=services]')
                // Fill the form (input + select + textarea + checkbox).
                ->type('business_name', 'Elman Group')
                ->select('business_type', 'ProfessionalService')
                ->type('description', 'Custom Laravel CMS and AI integration studio.')
                ->type('services', "Laravel development\nAI / MCP integration")
                ->type('service_area', 'Baku, Azerbaijan; Remote, EU')
                ->check('include_in_llms')
                ->scrollIntoView('form[action*="geo-settings"] button[type=submit]')
                ->click('form[action*="geo-settings"] button[type=submit]');

            // Saved -> success flash.
            $browser->waitForText('updated')
                ->assertSee('updated');

            // The data now drives the public machine-readable surface.
            $browser->visit('/llms.txt')
                ->assertSee('## Services')
                ->assertSee('AI / MCP integration');
        });
    }
}

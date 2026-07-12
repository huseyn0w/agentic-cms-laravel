<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\CPanel\CPanelGeneralSettings;
use App\Http\Models\User;
use App\Http\Models\UserRoles;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke coverage for the repository whitelisting refactor (Fix #5): the real
 * admin write flows must still persist correctly now that repositories receive
 * validated() data instead of $request->all().
 */
class CPanelWriteFlowSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        // These smoke tests POST directly to the admin write endpoints without
        // first rendering a Blade form, so no CSRF token is present in the
        // session/request. The CSRF middleware would short-circuit with a 419
        // before the controller ever runs (a 419 also leaves the validation
        // error bag empty, so assertSessionHasNoErrors() would not catch it).
        // We only exercise the controller -> repository -> persistence path
        // here, so we exclude the app's real VerifyCsrfToken middleware. The
        // production CSRF protection is unchanged.
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('username', 'admin')->firstOrFail();
    }

    public function test_admin_can_update_general_settings(): void
    {
        $this->actingAs($this->admin)
            ->post('/agentic-cms-laravel-admin/general-settings', [
                'website_name' => 'New Site Name',
                'tagline' => 'New Tagline',
                'posts_per_page' => 7,
                'comments_per_page' => 4,
                'contact_email' => 'hello@example.com',
                'membership' => 'on',
                'active_template_name' => 'default',
            ])
            ->assertSessionHasNoErrors();

        $settings = CPanelGeneralSettings::first();
        $this->assertSame('New Site Name', $settings->website_name);
        $this->assertSame(7, (int) $settings->posts_per_page);
        $this->assertSame(1, (int) $settings->membership);
    }

    public function test_admin_can_create_role_with_whitelisted_permissions(): void
    {
        $this->actingAs($this->admin)
            ->post('/agentic-cms-laravel-admin/roles/new', [
                'name' => 'Editor',
                'permissions' => ['manage_posts', 'manage_pages', 'not_a_real_permission'],
            ])
            ->assertSessionHasNoErrors();

        $role = UserRoles::where('name', 'Editor')->firstOrFail();
        $permissions = json_decode($role->permissions, true);

        $this->assertSame(1, $permissions['manage_posts']);
        $this->assertSame(1, $permissions['manage_pages']);
        $this->assertSame(0, $permissions['manage_users']);
        // Bogus permission keys must never be stored.
        $this->assertArrayNotHasKey('not_a_real_permission', $permissions);
    }
}

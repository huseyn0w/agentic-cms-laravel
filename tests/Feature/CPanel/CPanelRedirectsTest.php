<?php

namespace Tests\Feature\CPanel;

use App\Http\Models\Redirect;
use App\Http\Models\User;
use App\Http\Models\UserRoles;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Admin redirect manager: renders, creates (normalizing the source and busting
 * the resolver cache so it fires immediately), deletes, and is gated by
 * manage_general_settings. Plus the CSV import command.
 */
class CPanelRedirectsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.ensure_pages_exist' => false]);
        $this->seed(DatabaseSeeder::class);
    }

    private function admin(): User
    {
        return User::where('username', 'admin')->firstOrFail();
    }

    private function nonManager(): User
    {
        $role = UserRoles::create([
            'name' => 'role_'.bin2hex(random_bytes(4)),
            'permissions' => json_encode(['see_admin_panel' => 1, 'manage_general_settings' => 0]),
        ]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_page_renders(): void
    {
        Redirect::create(['source_path' => '/old', 'target' => '/new', 'status_code' => 301]);

        $this->actingAs($this->admin())
            ->get(route('cpanel_redirects'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/redirects/Index')
                ->has('redirects.data', 1));
    }

    public function test_requires_permission(): void
    {
        $this->actingAs($this->nonManager())
            ->get(route('cpanel_redirects'))
            ->assertStatus(401);
    }

    public function test_store_normalizes_source_and_takes_effect_immediately(): void
    {
        $this->actingAs($this->admin())
            ->post(route('cpanel_redirects_store'), [
                'source_path' => '/Old-Path/',
                'target' => '/new-path',
                'status_code' => 301,
            ])
            ->assertRedirect();

        // Trailing slash stripped on save.
        $this->assertDatabaseHas('redirects', ['source_path' => '/Old-Path', 'target' => '/new-path']);

        // Cache busted → the redirect fires on the very next request.
        $this->get('/Old-Path')->assertStatus(301)->assertRedirect('/new-path');
    }

    public function test_invalid_status_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post(route('cpanel_redirects_store'), [
                'source_path' => '/x',
                'target' => '/y',
                'status_code' => 307,
            ])
            ->assertSessionHasErrors('status_code');
    }

    public function test_destroy_removes_and_busts_cache(): void
    {
        $redirect = Redirect::create(['source_path' => '/gone', 'target' => '/here', 'status_code' => 301]);
        // Warm the cache.
        $this->get('/gone')->assertStatus(301);

        $this->actingAs($this->admin())
            ->delete(route('cpanel_redirects_destroy', $redirect->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('redirects', ['id' => $redirect->id]);
        // No longer redirects.
        $response = $this->get('/gone');
        $this->assertNotContains($response->getStatusCode(), [301, 302]);
    }

    public function test_import_command_bulk_loads_a_csv(): void
    {
        $csv = tempnam(sys_get_temp_dir(), 'red').'.csv';
        file_put_contents($csv, "/a,/one,301\n/b/,/two,302\n,,\n/c,/three\n");

        $this->artisan('cms:import-redirects', ['file' => $csv])
            ->expectsOutputToContain('Imported 3')
            ->assertSuccessful();

        @unlink($csv);

        $this->assertDatabaseHas('redirects', ['source_path' => '/a', 'target' => '/one', 'status_code' => 301]);
        $this->assertDatabaseHas('redirects', ['source_path' => '/b', 'target' => '/two', 'status_code' => 302]);
        $this->assertDatabaseHas('redirects', ['source_path' => '/c', 'target' => '/three', 'status_code' => 301]);
    }
}

<?php

namespace Tests\Feature\Newsletter;

use App\Http\Models\NewsletterSubscriber;
use App\Http\Models\User;
use App\Http\Models\UserRoles;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AdminNewsletterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function adminUser(): User
    {
        // The seeded Administrator role (id 1) has every permission.
        return User::factory()->create(['role_id' => 1]);
    }

    public function test_panel_user_without_manage_newsletter_is_denied(): void
    {
        $role = UserRoles::create([
            'name' => 'PanelNoNewsletter',
            'permissions' => json_encode(['see_admin_panel' => 1, 'manage_newsletter' => 0]),
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get('/agentic-cms-laravel-admin/newsletter')->assertStatus(401);
    }

    public function test_seeded_administrator_has_manage_newsletter(): void
    {
        // UserPolicy reads Auth::user() in its constructor, so the user must be
        // authenticated for can() to resolve their role permissions.
        $this->actingAs($this->adminUser());

        $this->assertTrue(auth()->user()->can('manage_newsletter', UserRoles::class));
    }

    public function test_permission_row_exists(): void
    {
        $this->assertDatabaseHas('user_permissions', ['name' => 'manage_newsletter']);
    }

    public function test_migration_backfills_full_access_role(): void
    {
        // A full-access role created WITHOUT the flag (simulating a live install
        // before this migration) is backfilled to 1 by re-running up().
        $role = UserRoles::create([
            'name' => 'LegacyAdmin',
            'permissions' => json_encode([
                'see_admin_panel' => 1, 'manage_users' => 1, 'manage_posts' => 1,
            ]),
        ]);

        (new \AddManageNewsletterPermission)->up();

        $perms = json_decode(DB::table('user_roles')->find($role->id)->permissions, true);
        $this->assertSame(1, $perms['manage_newsletter']);
    }

    public function test_index_lists_subscribers_with_props(): void
    {
        NewsletterSubscriber::factory()->confirmed()->create(['email' => 'one@example.com']);
        NewsletterSubscriber::factory()->create(['email' => 'two@example.com', 'status' => 'pending']);

        $this->actingAs($this->adminUser())
            ->get('/agentic-cms-laravel-admin/newsletter')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $p) => $p
                ->component('cpanel/newsletter/List')
                ->has('subscribers.data', 2));
    }

    public function test_index_filters_by_status_and_searches(): void
    {
        NewsletterSubscriber::factory()->confirmed()->create(['email' => 'keep@example.com']);
        NewsletterSubscriber::factory()->create(['email' => 'drop@example.com', 'status' => 'pending']);

        $this->actingAs($this->adminUser())
            ->get('/agentic-cms-laravel-admin/newsletter?status=confirmed')
            ->assertInertia(fn (AssertableInertia $p) => $p->has('subscribers.data', 1)->where('filters.status', 'confirmed'));

        $this->actingAs($this->adminUser())
            ->get('/agentic-cms-laravel-admin/newsletter?search=keep')
            ->assertInertia(fn (AssertableInertia $p) => $p->has('subscribers.data', 1));
    }

    public function test_store_adds_a_confirmed_admin_subscriber(): void
    {
        $this->actingAs($this->adminUser())
            ->post('/agentic-cms-laravel-admin/newsletter', ['email' => 'manual@example.com'])
            ->assertRedirect();

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'manual@example.com', 'status' => 'confirmed', 'source' => 'admin',
        ]);
    }

    public function test_store_rejects_duplicate_email(): void
    {
        NewsletterSubscriber::factory()->create(['email' => 'exists@example.com']);

        $this->actingAs($this->adminUser())
            ->post('/agentic-cms-laravel-admin/newsletter', ['email' => 'exists@example.com'])
            ->assertSessionHasErrors('email');
    }

    public function test_destroy_removes_a_subscriber(): void
    {
        $sub = NewsletterSubscriber::factory()->create();

        $this->actingAs($this->adminUser())
            ->delete("/agentic-cms-laravel-admin/newsletter/{$sub->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('newsletter_subscribers', ['id' => $sub->id]);
    }

    public function test_export_streams_confirmed_csv(): void
    {
        NewsletterSubscriber::factory()->confirmed()->create(['email' => 'export@example.com']);
        NewsletterSubscriber::factory()->create(['email' => 'skip@example.com', 'status' => 'pending']);

        $response = $this->actingAs($this->adminUser())
            ->get('/agentic-cms-laravel-admin/newsletter/export');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $csv = $response->streamedContent();
        $this->assertStringContainsString('export@example.com', $csv);
        $this->assertStringNotContainsString('skip@example.com', $csv);
    }

    public function test_every_admin_route_is_forbidden_without_permission(): void
    {
        $role = UserRoles::create([
            'name' => 'NoNews',
            'permissions' => json_encode(['see_admin_panel' => 1, 'manage_newsletter' => 0]),
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);
        $sub = NewsletterSubscriber::factory()->create();

        $this->actingAs($user)->post('/agentic-cms-laravel-admin/newsletter', ['email' => 'x@example.com'])->assertStatus(401);
        $this->actingAs($user)->delete("/agentic-cms-laravel-admin/newsletter/{$sub->id}")->assertStatus(401);
        $this->actingAs($user)->get('/agentic-cms-laravel-admin/newsletter/export')->assertStatus(401);
    }
}

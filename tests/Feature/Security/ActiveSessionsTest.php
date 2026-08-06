<?php

namespace Tests\Feature\Security;

use App\Http\Models\CPanel\CPanelSession;
use App\Http\Models\User;
use App\Services\CPanel\CPanelSessionService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Active browser sessions + force-logout. The database session store backs a
 * self-service list on the profile: a user can revoke a single session or log
 * out all other sessions (password-guarded). Every action is scoped to the
 * current user, so no one can touch another account's sessions.
 */
class ActiveSessionsTest extends TestCase
{
    use RefreshDatabase;

    private const REVOKE = '/agentic-cms-laravel-admin/myprofile/sessions/';

    private const LOGOUT_OTHERS = '/agentic-cms-laravel-admin/myprofile/sessions/logout-others';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config(['inertia.testing.ensure_pages_exist' => false]);
    }

    /** The seeded admin (has see_admin_panel), with a known password. */
    private function admin(string $password = 'Known123!'): User
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $admin->password = $password;
        $admin->save();

        return $admin->fresh();
    }

    private function putSession(string $id, int $userId, string $ua = 'Mozilla/5.0 (Windows NT 10.0) Chrome/120'): void
    {
        CPanelSession::create([
            'id' => $id,
            'user_id' => $userId,
            'ip_address' => '127.0.0.1',
            'user_agent' => $ua,
            'payload' => 'x',
            'last_activity' => now()->timestamp,
        ]);
    }

    public function test_service_lists_sessions_with_current_flag_and_device_label(): void
    {
        $admin = $this->admin();
        $this->putSession('sess-b', $admin->id, 'Mozilla/5.0 (Macintosh) Firefox/121');
        $this->putSession('sess-a', $admin->id, 'Mozilla/5.0 (Windows NT 10.0) Chrome/120');

        $out = app(CPanelSessionService::class)->activeSessions($admin->id, 'sess-a');

        $this->assertCount(2, $out);
        // Current session is flagged and floated to the top.
        $this->assertSame('sess-a', $out[0]['id']);
        $this->assertTrue($out[0]['is_current']);
        $this->assertSame('Chrome · Windows', $out[0]['device']);
        $this->assertFalse($out[1]['is_current']);
        $this->assertSame('Firefox · macOS', $out[1]['device']);
    }

    public function test_revoke_deletes_the_users_own_session(): void
    {
        $admin = $this->admin();
        $this->putSession('mine', $admin->id);

        $this->actingAs($admin)->delete(self::REVOKE.'mine')->assertRedirect();

        $this->assertDatabaseMissing('sessions', ['id' => 'mine']);
    }

    public function test_revoke_cannot_touch_another_users_session(): void
    {
        $admin = $this->admin();
        $other = User::factory()->create();
        $this->putSession('theirs', $other->id);

        $this->actingAs($admin)->delete(self::REVOKE.'theirs');

        // Scoped by user_id — the other account's session is untouched.
        $this->assertDatabaseHas('sessions', ['id' => 'theirs']);
    }

    public function test_logout_others_requires_the_current_password(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(self::LOGOUT_OTHERS, ['password' => 'wrong-password'])
            ->assertSessionHasErrors('password');
    }

    public function test_logout_others_revokes_the_other_sessions(): void
    {
        $admin = $this->admin();
        $this->putSession('o1', $admin->id);
        $this->putSession('o2', $admin->id);

        $this->actingAs($admin)->post(self::LOGOUT_OTHERS, ['password' => 'Known123!'])
            ->assertRedirect();

        $this->assertDatabaseMissing('sessions', ['id' => 'o1']);
        $this->assertDatabaseMissing('sessions', ['id' => 'o2']);
    }

    public function test_profile_ships_the_sessions_prop_for_self(): void
    {
        $admin = $this->admin();
        $this->putSession('mine', $admin->id);

        $this->actingAs($admin)->get('/agentic-cms-laravel-admin/myprofile')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('cpanel/users/Form')
                ->has('sessions'));
    }
}

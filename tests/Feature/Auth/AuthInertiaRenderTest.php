<?php

namespace Tests\Feature\Auth;

use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AuthInertiaRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        // The React page components (resources/js/pages/auth/*.tsx) land in a
        // later task (plan step 11); the backend contract is verified first.
        // Scoped here (not globally) so tests against already-built pages
        // (e.g. Phase0SmokeTest's Demo.tsx) keep the on-disk existence check.
        config(['inertia.testing.ensure_pages_exist' => false]);
    }

    public function test_login_renders_inertia_component_with_props(): void
    {
        $this->get('/login')->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('auth/Login')
                ->has('canResetPassword')
                ->has('membershipEnabled')
        );
    }

    public function test_register_renders_inertia_component(): void
    {
        $this->get('/register')->assertInertia(
            fn (AssertableInertia $page) => $page->component('auth/Register')
        );
    }

    public function test_forgot_password_renders_inertia_component(): void
    {
        $this->get('/password/reset')->assertInertia(
            fn (AssertableInertia $page) => $page->component('auth/ForgotPassword')
        );
    }

    public function test_reset_password_renders_inertia_component_with_token(): void
    {
        $this->get('/password/reset/sample-token?email=a@b.com')->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('auth/ResetPassword')
                ->where('token', 'sample-token')
                ->where('email', 'a@b.com')
        );
    }

    public function test_verify_notice_renders_inertia_for_unverified_user(): void
    {
        $user = User::factory()->create([
            'role_id' => 2,
            'email_verified_at' => null,
        ]);

        $this->actingAs($user)->get('/email/verify')->assertInertia(
            fn (AssertableInertia $page) => $page->component('auth/VerifyEmail')
        );
    }
}

<?php

namespace Tests\Feature\Auth;

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
}

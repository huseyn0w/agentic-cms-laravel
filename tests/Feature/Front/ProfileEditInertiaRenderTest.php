<?php

namespace Tests\Feature\Front;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * The authenticated self-service profile screens are on Inertia: the profile
 * edit form (public/ProfileEdit) and the change-password form
 * (public/ChangePassword). Both are noindex; the head stays Blade.
 */
class ProfileEditInertiaRenderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        config(['inertia.testing.ensure_pages_exist' => false]);
        $this->seed(DatabaseSeeder::class);
        $this->user = User::factory()->create(['role_id' => 2, 'name' => 'Jane', 'email' => 'jane@example.test']);
    }

    public function test_profile_edit_renders_the_form_with_current_values(): void
    {
        $this->actingAs($this->user)->get('/profile/edit')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('public/ProfileEdit')
                ->has('shell.menu')
                ->where('action', route('update_user_info'))
                ->where('profile.email', 'jane@example.test')
                ->where('profile.name', 'Jane')
                ->has('countries')
        );
    }

    public function test_change_password_renders_the_form(): void
    {
        $this->actingAs($this->user)->get('/profile/change_password')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('public/ChangePassword')
                ->where('action', route('change_password_action'))
                ->has('csrfToken')
        );
    }

    public function test_profile_edit_is_noindex(): void
    {
        $this->actingAs($this->user)->get('/profile/edit')->assertOk()->assertSee('noindex', false);
    }
}

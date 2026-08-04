<?php

namespace Tests\Feature\CPanel;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class UserListInertiaRenderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->seed(DatabaseSeeder::class);
        config(['inertia.testing.ensure_pages_exist' => false]);
        $this->admin = User::where('username', 'admin')->firstOrFail();
    }

    public function test_list_renders_inertia_component_with_shaped_rows(): void
    {
        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/users')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/users/List')
                ->has('users_list.last_page')
                ->where('users_list.data', function ($rows) {
                    $row = collect($rows)->firstWhere('username', 'admin');

                    return $row !== null
                        && array_key_exists('id', $row)
                        && array_key_exists('email', $row)
                        && array_key_exists('role', $row);
                }));
    }

    public function test_new_user_form_renders_inertia_with_null_entity_and_options(): void
    {
        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/users/new')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/users/Form')
                ->where('entity', null)
                ->has('countries.0.name')
                ->has('user_roles.0.id')
                ->has('user_roles.0.name'));
    }

    public function test_edit_user_form_renders_inertia_with_shaped_entity(): void
    {
        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/users/'.$this->admin->id)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/users/Form')
                ->where('entity.username', 'admin')
                ->where('entity.id', $this->admin->id)
                ->has('entity.role_id')
                ->has('entity.email'));
    }

    public function test_create_user_accepts_json_role_id_as_integer(): void
    {
        $payload = [
            'username' => 'jsonuser',
            'email' => 'jsonuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'name' => 'Json',
            'surname' => 'User',
            'country' => 'Germany',
            'city' => 'Berlin',
            'role_id' => $this->admin->role_id,
            'gender' => 'male',
        ];

        $this->actingAs($this->admin)
            ->postJson('/agentic-cms-laravel-admin/users/new', $payload)
            ->assertRedirect(route('cpanel_all_users_list'));

        $this->assertDatabaseHas('users', ['username' => 'jsonuser', 'email' => 'jsonuser@example.com']);
    }
}

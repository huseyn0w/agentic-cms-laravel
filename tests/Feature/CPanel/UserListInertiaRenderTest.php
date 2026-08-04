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
}

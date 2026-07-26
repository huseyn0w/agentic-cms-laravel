<?php

namespace Tests\Feature\CPanel;

use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CategoryInertiaRenderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config(['inertia.testing.ensure_pages_exist' => false]);
        $this->admin = User::where('username', 'admin')->firstOrFail();
    }

    public function test_list_renders_inertia_component_with_pagination(): void
    {
        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/categories')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/categories/List')
                ->has('categories_list.data'));
    }
}

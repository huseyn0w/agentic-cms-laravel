<?php

namespace Tests\Feature\CPanel;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\ServiceTranslation;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ServiceListInertiaRenderTest extends TestCase
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

    private function createService(string $title, string $slug): int
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/services/new', [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => 'x',
            'content' => 'body',
            'meta_keywords' => 'kw',
            'meta_description' => 'md',
            'sort_order' => 1,
            'status' => 1,
        ]);

        return ServiceTranslation::where('slug', $slug)->firstOrFail()->service_id;
    }

    public function test_list_renders_inertia_component_with_shaped_rows_and_trashed_false(): void
    {
        $this->createService('Visible service', 'visible-service');

        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/services')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/services/List')
                ->where('trashed', false)
                // Paginator meta must survive the controller's row transform so
                // the <Pagination> pager can navigate.
                ->where('services_list.current_page', 1)
                ->has('services_list.last_page')
                ->has('services_list.next_page_url')
                ->where('services_list.data', function ($rows) {
                    $row = collect($rows)->firstWhere('title', 'Visible service');

                    return $row !== null
                        && array_key_exists('id', $row)
                        && array_key_exists('sort_order', $row)
                        && array_key_exists('status', $row);
                }));
    }

    public function test_trashed_list_renders_the_same_component_with_trashed_true(): void
    {
        $serviceId = $this->createService('Trash me', 'trash-me-service');
        $this->actingAs($this->admin)->delete('/agentic-cms-laravel-admin/services/'.$serviceId.'/delete');

        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/services/trashed')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/services/List')
                ->where('trashed', true)
                ->where('services_list.data', fn ($rows) => collect($rows)->contains('title', 'Trash me')));
    }
}

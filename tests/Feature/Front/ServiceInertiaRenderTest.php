<?php

namespace Tests\Feature\Front;

use App\Http\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Services index + detail on Inertia. Body is React; the ItemList JSON-LD and
 * the seo-meta head stay server-rendered by Blade.
 */
class ServiceInertiaRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.ensure_pages_exist' => false]);
    }

    private function publish(string $slug, int $status = 1, int $order = 0): Service
    {
        $service = Service::create(['sort_order' => $order]);
        $t = $service->translateOrNew('en');
        $t->title = ucfirst($slug);
        $t->slug = $slug;
        $t->excerpt = 'Summary of '.$slug;
        $t->content = '<p>Body of '.$slug.'</p>';
        $t->status = $status;
        $service->save();

        return $service;
    }

    public function test_services_index_renders_the_component_with_cards(): void
    {
        $this->publish('alpha');

        $this->get('/services')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('public/ServiceIndex')
                ->has('shell.menu')
                ->has('heading')
                ->where('services.0.title', 'Alpha')
                ->has('services.0.url')
        );
    }

    public function test_services_index_ships_itemlist_json_ld(): void
    {
        $this->publish('alpha');

        $html = $this->get('/services')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<title>'));
        $this->assertStringContainsString('"@type": "ItemList"', $html);
        $this->assertStringContainsString('"@type": "Service"', $html);
    }

    public function test_service_detail_renders_the_component(): void
    {
        $this->publish('beta');

        $this->get('/services/beta')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('public/ServiceShow')
                ->where('service.title', 'Beta')
                ->has('service.content')
                ->has('crumbs')
        );
    }

    public function test_draft_service_detail_404s(): void
    {
        $this->publish('secret', 0);

        $this->get('/services/secret')->assertNotFound();
    }
}

<?php

namespace Tests\Feature\Seo;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * End-to-end wiring: with the flag on for an allow-listed route, Inertia's
 * HttpGateway swaps the server-rendered body into the page; with the renderer
 * gone, the page still serves, client-rendered, exactly as before SSR existed.
 *
 * `/inertia-demo` (route `inertia_demo`) is a real public Inertia route, used
 * here as the probe so the whole middleware -> Inertia -> gateway path runs.
 */
class SsrRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        config([
            'inertia.ssr.public.enabled' => true,
            'inertia.ssr.public.routes' => ['inertia_demo'],
            // The bundle is a build artifact absent in CI; the fake stands in
            // for the Node process, so the gateway must not gate on the file.
            'inertia.ssr.ensure_bundle_exists' => false,
        ]);
    }

    public function test_it_puts_the_server_rendered_body_into_the_page(): void
    {
        Http::fake([
            '127.0.0.1:13714/*' => Http::response([
                'head' => [],
                'body' => '<div id="app" data-page="{}"><h1>Rendered on the server</h1></div>',
            ]),
        ]);

        $html = $this->get('/inertia-demo')->assertOk()->getContent();

        $this->assertStringContainsString('<h1>Rendered on the server</h1>', $html);
    }

    public function test_it_still_serves_the_page_when_the_renderer_is_unreachable(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection refused'));

        $html = $this->get('/inertia-demo')->assertOk()->getContent();

        // Falls back to the ordinary client-rendered shell.
        $this->assertStringContainsString('<div id="app"', $html);
    }
}

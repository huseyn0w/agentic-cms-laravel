<?php

namespace Tests\Feature\Seo;

use App\Services\Seo\SsrProcess;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The renderer is a plain Node process this app talks to over HTTP. SsrProcess
 * is the thin layer that knows whether it is switched on, whether it is
 * answering, and where the Node binary lives on a host that keeps it off PATH.
 */
class SsrProcessTest extends TestCase
{
    public function test_is_enabled_reflects_the_master_switch(): void
    {
        config(['inertia.ssr.public.enabled' => true]);
        $this->assertTrue(app(SsrProcess::class)->isEnabled());

        config(['inertia.ssr.public.enabled' => false]);
        $this->assertFalse(app(SsrProcess::class)->isEnabled());
    }

    public function test_is_running_is_true_when_health_answers(): void
    {
        Http::fake(['127.0.0.1:13714/health' => Http::response(['status' => 'OK'])]);

        $this->assertTrue(app(SsrProcess::class)->isRunning());
    }

    public function test_is_running_is_false_when_the_renderer_is_unreachable(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection refused'));

        $this->assertFalse(app(SsrProcess::class)->isRunning());
    }

    public function test_node_binary_honours_an_explicit_override(): void
    {
        // /bin/sh is always present and executable in the container; it stands
        // in for a real node path without needing Node installed to test.
        config(['inertia.ssr.public.node_binary' => '/bin/sh']);

        $this->assertSame('/bin/sh', app(SsrProcess::class)->nodeBinary());
    }

    public function test_node_binary_ignores_an_override_that_is_not_executable(): void
    {
        config(['inertia.ssr.public.node_binary' => '/no/such/node']);

        // Falls through to discovery, which on this host finds either a real
        // node or nothing - never the bogus override.
        $this->assertNotSame('/no/such/node', app(SsrProcess::class)->nodeBinary());
    }

    public function test_bundle_path_points_at_the_ssr_bundle(): void
    {
        $this->assertSame(
            base_path('bootstrap/ssr/ssr.js'),
            app(SsrProcess::class)->bundlePath()
        );
    }
}

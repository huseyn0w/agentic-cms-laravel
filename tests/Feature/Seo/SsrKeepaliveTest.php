<?php

namespace Tests\Feature\Seo;

use App\Services\Seo\SsrProcess;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The renderer is a plain background process, so something has to notice when
 * it is gone. This is that something, and it must stay quiet the rest of the
 * time - it runs every five minutes forever.
 */
class SsrKeepaliveTest extends TestCase
{
    public function test_does_nothing_while_rendering_is_switched_off(): void
    {
        config(['inertia.ssr.public.enabled' => false]);

        Http::preventStrayRequests();

        $this->artisan('ssr:keepalive')
            ->expectsOutputToContain('off')
            ->assertSuccessful();
    }

    public function test_leaves_a_healthy_renderer_alone(): void
    {
        $this->mock(SsrProcess::class, function ($mock) {
            $mock->shouldReceive('isEnabled')->andReturn(true);
            $mock->shouldReceive('isRunning')->andReturn(true);
            $mock->shouldReceive('start')->never();
        });

        $this->artisan('ssr:keepalive')->assertSuccessful();
    }

    public function test_starts_a_renderer_that_is_not_answering(): void
    {
        $this->mock(SsrProcess::class, function ($mock) {
            $mock->shouldReceive('isEnabled')->andReturn(true);
            $mock->shouldReceive('isRunning')->andReturn(false);
            $mock->shouldReceive('start')->once()->andReturn(true);
        });

        $this->artisan('ssr:keepalive')
            ->expectsOutputToContain('Started')
            ->assertSuccessful();
    }

    /**
     * A host with no Node is a fact of life, not an incident: the site is
     * simply client-rendered. Failing the command would turn that into a red
     * scheduler every five minutes.
     */
    public function test_reports_rather_than_fails_when_there_is_nothing_to_start(): void
    {
        $this->mock(SsrProcess::class, function ($mock) {
            $mock->shouldReceive('isEnabled')->andReturn(true);
            $mock->shouldReceive('isRunning')->andReturn(false);
            $mock->shouldReceive('start')->once()->andReturn(false);
        });

        $this->artisan('ssr:keepalive')
            ->expectsOutputToContain('client-rendered')
            ->assertSuccessful();
    }
}

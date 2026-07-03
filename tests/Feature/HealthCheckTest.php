<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * FEATURE_MATRIX §15 — liveness + readiness endpoints, mirroring ts's
 * /health (liveness, no dependencies) and /health/ready (readiness with a DB
 * probe). Both return 200 when the app is up and the DB reachable.
 */
class HealthCheckTest extends TestCase
{
    public function test_health_liveness_returns_ok(): void
    {
        $response = $this->get('/health');

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'ok');
    }

    public function test_health_ready_returns_ok_when_db_up(): void
    {
        $response = $this->get('/health/ready');

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'ok');
        $response->assertJsonPath('database', 'up');
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\HealthCheckService;

/**
 * Liveness + readiness endpoints (FEATURE_MATRIX §15), mirroring ts's
 * /health and /health/ready.
 *
 * - GET /health        — liveness. Returns 200 immediately with no dependency
 *                        checks; proves the process is up and serving.
 * - GET /health/ready  — readiness. Runs a cheap DB probe (SELECT 1) via the
 *                        service/repository layer; returns 200 database:"up"
 *                        when reachable, 503 database:"down" otherwise, so
 *                        orchestrators can gate traffic until the DB is ready.
 */
class HealthController extends Controller
{
    public function __construct(private HealthCheckService $health) {}

    public function live()
    {
        return response()->json([
            'status' => 'ok',
            'service' => config('app.name'),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function ready()
    {
        $dbUp = $this->health->databaseIsUp();

        return response()->json([
            'status' => $dbUp ? 'ok' : 'error',
            'database' => $dbUp ? 'up' : 'down',
        ], $dbUp ? 200 : 503);
    }
}

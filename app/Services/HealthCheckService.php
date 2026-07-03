<?php

namespace App\Services;

use App\Repositories\HealthRepository;

/**
 * Health service (FEATURE_MATRIX §15): keeps the readiness DB probe behind the
 * repository so the controller stays a pure HTTP boundary.
 */
class HealthCheckService
{
    public function __construct(private HealthRepository $repo) {}

    public function databaseIsUp(): bool
    {
        return $this->repo->databaseIsUp();
    }
}

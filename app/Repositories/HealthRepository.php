<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

/**
 * Readiness probe home (FEATURE_MATRIX §15). Repositories are the only layer
 * permitted to touch the DB, so the SELECT-1 liveness ping for the database
 * lives here rather than in the controller.
 */
class HealthRepository
{
    /**
     * True when the database answers a trivial query; false on any failure
     * (connection refused, timeout, etc.) — never throws.
     */
    public function databaseIsUp(): bool
    {
        try {
            DB::connection()->select('select 1');

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}

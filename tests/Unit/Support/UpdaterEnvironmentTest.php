<?php

namespace Tests\Unit\Support;

use App\Support\Updater\Environment;
use Tests\TestCase;

/**
 * The updater adapts to the host instead of assuming one environment. Environment
 * reports what a given host can actually do — proc_open, composer, node, write
 * access — so UpdateService can pick a safe path (shipped vendor vs composer,
 * ship prebuilt vs build) on local / shared / VPS alike.
 */
class UpdaterEnvironmentTest extends TestCase
{
    public function test_reports_proc_open_availability_as_bool(): void
    {
        $this->assertIsBool((new Environment)->hasProcOpen());
    }

    public function test_reports_composer_and_node_as_bool_without_throwing(): void
    {
        $env = new Environment;

        // We can't assert the value (host-dependent), only that probing is safe.
        $this->assertIsBool($env->hasComposer());
        $this->assertIsBool($env->hasNode());
    }

    public function test_php_version_matches_runtime(): void
    {
        $this->assertSame(PHP_VERSION, (new Environment)->phpVersion());
    }

    public function test_can_write_is_true_for_a_writable_dir_and_false_for_a_bogus_path(): void
    {
        $env = new Environment;

        $this->assertTrue($env->canWrite(sys_get_temp_dir()));
        $this->assertFalse($env->canWrite('/no/such/root/'.bin2hex(random_bytes(4))));
    }

    public function test_summary_exposes_the_capability_flags(): void
    {
        $summary = (new Environment)->summary();

        $this->assertArrayHasKey('proc_open', $summary);
        $this->assertArrayHasKey('composer', $summary);
        $this->assertArrayHasKey('node', $summary);
        $this->assertArrayHasKey('php_version', $summary);
    }
}

<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * The CMS core carries its own version identity, decoupled from the framework
 * version. This underpins the WordPress-style core-update system: the updater
 * compares config('cms.version') against the release feed.
 */
class CmsVersionTest extends TestCase
{
    public function test_cms_config_exposes_a_semver_version(): void
    {
        $version = config('cms.version');

        $this->assertIsString($version);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $version);
    }

    public function test_cms_version_helper_returns_the_configured_version(): void
    {
        config(['cms.version' => '1.2.3']);

        $this->assertSame('1.2.3', cms_version());
    }

    public function test_cms_config_declares_update_defaults(): void
    {
        $update = config('cms.update');

        $this->assertIsArray($update);
        $this->assertArrayHasKey('channel', $update);
        $this->assertArrayHasKey('public_key', $update);
        $this->assertArrayHasKey('install_composer', $update);
        $this->assertArrayHasKey('check_schedule', $update);

        // install_composer stays off by default: the shipped vendor/ is used
        // unless a host opts in and composer is actually reachable.
        $this->assertFalse($update['install_composer']);

        // The auto-check cadence is one of the two supported values.
        $this->assertContains($update['check_schedule'], ['hourly', 'daily']);
    }
}

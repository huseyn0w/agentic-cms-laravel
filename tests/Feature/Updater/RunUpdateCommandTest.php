<?php

namespace Tests\Feature\Updater;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Smoke: the cms:update command is registered and, with no channel configured,
 * reports the core as up to date rather than erroring.
 */
class RunUpdateCommandTest extends TestCase
{
    public function test_the_update_command_is_registered(): void
    {
        $this->assertArrayHasKey('cms:update', Artisan::all());
    }

    public function test_check_reports_up_to_date_when_no_channel_is_configured(): void
    {
        config(['cms.update.channel' => '']);

        $this->artisan('cms:update', ['--check' => true])
            ->expectsOutputToContain('up to date')
            ->assertSuccessful();
    }
}

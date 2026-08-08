<?php

namespace Tests\Feature\Updater;

use App\Support\Updater\UpdateService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The scheduled cms:check-updates command refreshes the cached availability
 * from the feed, so the admin banner reflects it without a live call per page.
 */
class CheckUpdatesCommandTest extends TestCase
{
    public function test_it_caches_an_available_release_from_the_feed(): void
    {
        config(['cms.update.channel' => 'https://feed.test/releases.json']);
        Http::fake(['https://feed.test/releases.json' => Http::response(['releases' => [
            ['version' => '5.0.0', 'url' => 'https://x/5.tar.gz', 'sha256' => 'abc'],
        ]])]);

        $this->artisan('cms:check-updates')
            ->expectsOutputToContain('Update available: 5.0.0')
            ->assertSuccessful();

        $this->assertSame('5.0.0', Cache::get(UpdateService::CACHE_KEY)['version']);
    }

    public function test_it_caches_null_when_up_to_date(): void
    {
        config(['cms.update.channel' => 'https://feed.test/releases.json']);
        Http::fake(['https://feed.test/releases.json' => Http::response(['releases' => [
            ['version' => '0.0.1', 'url' => 'https://x/old.tar.gz', 'sha256' => 'abc'],
        ]])]);

        $this->artisan('cms:check-updates')
            ->expectsOutputToContain('up to date')
            ->assertSuccessful();

        $this->assertNull(Cache::get(UpdateService::CACHE_KEY));
    }
}

<?php

namespace Tests\Unit\Support;

use App\Support\Updater\ReleaseFeed;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ReleaseFeed reads the update channel, parses the release list, and — by semver
 * — decides whether a newer release than the installed version is available.
 * This is what "Check for updates" and the background check both call.
 */
class ReleaseFeedTest extends TestCase
{
    private const FEED = 'https://releases.example.test/feed.json';

    private function fakeFeed(array $releases): void
    {
        Http::fake([self::FEED => Http::response(['releases' => $releases])]);
    }

    private function feed(): ReleaseFeed
    {
        return new ReleaseFeed(self::FEED);
    }

    public function test_latest_picks_the_highest_semver(): void
    {
        $this->fakeFeed([
            ['version' => '1.0.0', 'url' => 'https://x/1.tar.gz', 'sha256' => 'a'],
            ['version' => '1.3.0', 'url' => 'https://x/3.tar.gz', 'sha256' => 'b'],
            ['version' => '1.2.0', 'url' => 'https://x/2.tar.gz', 'sha256' => 'c'],
        ]);

        $this->assertSame('1.3.0', $this->feed()->latest()['version']);
    }

    public function test_available_returns_a_newer_release_than_current(): void
    {
        $this->fakeFeed([
            ['version' => '1.0.0', 'url' => 'https://x/1.tar.gz', 'sha256' => 'a'],
            ['version' => '2.1.0', 'url' => 'https://x/2.tar.gz', 'sha256' => 'b'],
        ]);

        $release = $this->feed()->available('1.0.0');

        $this->assertNotNull($release);
        $this->assertSame('2.1.0', $release['version']);
    }

    public function test_available_is_null_when_current_is_up_to_date(): void
    {
        $this->fakeFeed([
            ['version' => '1.0.0', 'url' => 'https://x/1.tar.gz', 'sha256' => 'a'],
            ['version' => '1.2.0', 'url' => 'https://x/2.tar.gz', 'sha256' => 'b'],
        ]);

        $this->assertNull($this->feed()->available('1.2.0'));
        $this->assertNull($this->feed()->available('1.5.0'));
    }

    public function test_accepts_a_bare_array_feed_without_a_releases_key(): void
    {
        Http::fake([self::FEED => Http::response([
            ['version' => '1.1.0', 'url' => 'https://x/1.tar.gz', 'sha256' => 'a'],
        ])]);

        $this->assertSame('1.1.0', $this->feed()->latest()['version']);
    }

    public function test_unreachable_or_empty_feed_yields_null_not_an_error(): void
    {
        Http::fake([self::FEED => Http::response('', 500)]);

        $this->assertNull($this->feed()->latest());
        $this->assertNull($this->feed()->available('1.0.0'));
    }

    public function test_empty_channel_is_disabled(): void
    {
        $this->assertNull((new ReleaseFeed(''))->latest());
    }
}

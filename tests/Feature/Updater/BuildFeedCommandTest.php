<?php

namespace Tests\Feature\Updater;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * cms:build-feed turns the build artifacts (archive sha256, signature, and
 * release-manifest.json) into the flat releases.json feed that ReleaseFeed
 * reads. CI runs it after packaging a release and commits the feed so each
 * fleet site's update channel can point at it.
 */
class BuildFeedCommandTest extends TestCase
{
    private string $dir;

    private string $feed;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir().'/cms-feed-'.bin2hex(random_bytes(4));
        mkdir($this->dir, 0777, true);
        $this->feed = $this->dir.'/releases.json';
    }

    protected function tearDown(): void
    {
        $items = @scandir($this->dir) ?: [];
        foreach ($items as $item) {
            if ($item !== '.' && $item !== '..') {
                @unlink($this->dir.'/'.$item);
            }
        }
        @rmdir($this->dir);
        parent::tearDown();
    }

    /** Write the three build artifacts for a version into the build dir. */
    private function writeArtifacts(string $version, string $sha256, ?string $signature = 'sig-'): void
    {
        $archive = 'release-'.$version.'.tar.gz';
        file_put_contents($this->dir.'/'.$archive.'.sha256', $sha256);
        if ($signature !== null) {
            file_put_contents($this->dir.'/'.$archive.'.sig', base64_encode($signature.$version));
        }
        file_put_contents($this->dir.'/release-manifest.json', json_encode([
            'version' => $version,
            'min_php' => '8.2.0',
            'min_from_version' => '0.0.0',
            'files' => [],
        ]));
    }

    private function buildFeed(string $version): int
    {
        return Artisan::call('cms:build-feed', [
            'version' => $version,
            '--dir' => $this->dir,
            '--repo-url' => 'https://github.com/acme/cms',
            '--feed' => $this->feed,
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function releases(): array
    {
        $decoded = json_decode((string) file_get_contents($this->feed), true);

        return $decoded['releases'];
    }

    public function test_the_command_is_registered(): void
    {
        $this->assertArrayHasKey('cms:build-feed', Artisan::all());
    }

    public function test_it_writes_a_flat_entry_the_feed_reader_can_consume(): void
    {
        $sha = str_repeat('a', 64);
        $this->writeArtifacts('1.0.1', $sha);

        $this->assertSame(0, $this->buildFeed('1.0.1'));

        $releases = $this->releases();
        $this->assertCount(1, $releases);

        $entry = $releases[0];
        $this->assertSame('1.0.1', $entry['version']);
        $this->assertSame('https://github.com/acme/cms/releases/download/v1.0.1/release-1.0.1.tar.gz', $entry['url']);
        $this->assertSame($sha, $entry['sha256']);
        $this->assertSame(base64_encode('sig-1.0.1'), $entry['signature']);
        $this->assertSame('8.2.0', $entry['min_php']);
        $this->assertSame('0.0.0', $entry['min_from_version']);
    }

    public function test_it_upserts_a_version_without_duplicating_it(): void
    {
        $this->writeArtifacts('1.0.1', str_repeat('a', 64));
        $this->buildFeed('1.0.1');

        // Re-run the same version with a new checksum (a rebuild).
        $newSha = str_repeat('b', 64);
        $this->writeArtifacts('1.0.1', $newSha);
        $this->buildFeed('1.0.1');

        $releases = $this->releases();
        $this->assertCount(1, $releases);
        $this->assertSame($newSha, $releases[0]['sha256']);
    }

    public function test_it_keeps_releases_newest_first(): void
    {
        $this->writeArtifacts('1.0.1', str_repeat('a', 64));
        $this->buildFeed('1.0.1');
        $this->writeArtifacts('1.0.10', str_repeat('b', 64));
        $this->buildFeed('1.0.10');
        $this->writeArtifacts('1.0.2', str_repeat('c', 64));
        $this->buildFeed('1.0.2');

        $versions = array_column($this->releases(), 'version');
        $this->assertSame(['1.0.10', '1.0.2', '1.0.1'], $versions);
    }

    public function test_it_omits_the_signature_when_none_was_produced(): void
    {
        $this->writeArtifacts('1.0.1', str_repeat('a', 64), signature: null);

        $this->buildFeed('1.0.1');

        $this->assertArrayNotHasKey('signature', $this->releases()[0]);
    }
}

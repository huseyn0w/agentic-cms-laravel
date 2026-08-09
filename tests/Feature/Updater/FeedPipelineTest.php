<?php

namespace Tests\Feature\Updater;

use App\Support\Updater\PathManifest;
use App\Support\Updater\ReleaseBuilder;
use App\Support\Updater\ReleaseFeed;
use App\Support\Updater\Signer;
use App\Support\Updater\Verifier;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * End-to-end dry run of the release feed: a real ReleaseBuilder archive is
 * signed, cms:build-feed turns the artifacts into releases.json, and the feed
 * the updater reads reports the new release with a signature that verifies
 * against the public key. Proves the whole publish chain hangs together —
 * builder → feed command → ReleaseFeed → Verifier — without a container.
 */
class FeedPipelineTest extends TestCase
{
    private string $src;

    private string $out;

    protected function setUp(): void
    {
        parent::setUp();
        $this->src = sys_get_temp_dir().'/cms-pipe-src-'.bin2hex(random_bytes(4));
        $this->out = sys_get_temp_dir().'/cms-pipe-out-'.bin2hex(random_bytes(4));
        mkdir($this->src, 0777, true);
        mkdir($this->out, 0777, true);

        // A couple of core-owned fixture files so the build has content.
        $this->writeFixture('config/cms.php', "<?php return ['version' => '1.0.1'];");
        $this->writeFixture('public/build/manifest.json', '{}');
    }

    protected function tearDown(): void
    {
        $this->rrmdir($this->src);
        $this->rrmdir($this->out);
        parent::tearDown();
    }

    private function writeFixture(string $rel, string $contents): void
    {
        $full = $this->src.'/'.$rel;
        @mkdir(dirname($full), 0777, true);
        file_put_contents($full, $contents);
    }

    private function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($dir);
    }

    public function test_a_built_signed_release_flows_through_the_feed_to_the_reader(): void
    {
        $keys = Signer::generateKeypair();

        // 1. Build a real release archive (+ manifest + sha256).
        $builder = new ReleaseBuilder(new PathManifest(config('cms.paths')));
        $result = $builder->build($this->src, $this->out, '1.0.1');

        // 2. Sign it, exactly as CI does when CMS_RELEASE_SIGN_KEY is set.
        (new Signer)->sign($result['archive'], $keys['secret']);

        // 3. Build the feed from the artifacts.
        $feedPath = $this->out.'/releases.json';
        $this->assertSame(0, Artisan::call('cms:build-feed', [
            'version' => '1.0.1',
            '--dir' => $this->out,
            '--repo-url' => 'https://github.com/acme/cms',
            '--feed' => $feedPath,
        ]));

        // 4. Serve that feed on the channel and read it as the updater does.
        $channel = 'https://feed.test/releases.json';
        Http::fake([$channel => Http::response(json_decode((string) file_get_contents($feedPath), true))]);

        $release = (new ReleaseFeed($channel))->available('1.0.0');

        $this->assertNotNull($release);
        $this->assertSame('1.0.1', $release['version']);
        $this->assertStringEndsWith('/releases/download/v1.0.1/release-1.0.1.tar.gz', $release['url']);

        // 5. The feed's sha256 + signature match the real archive.
        $this->assertSame(hash_file('sha256', $result['archive']), $release['sha256']);
        $this->assertTrue(
            (new Verifier)->verify($result['archive'], $release['signature'], $keys['public'])
        );
    }
}

<?php

namespace App\Console\Commands;

use App\Support\Updater\FeedBuilder;
use Illuminate\Console\Command;

/**
 * Update the releases.json feed from the current build artifacts.
 *
 * Run by the CI release job after `cms:build-release`, once the archive, its
 * sha256, its signature, and release-manifest.json exist in the build dir. The
 * new entry is upserted into the feed the workflow then commits to the feed
 * branch. Each fleet site points its CMS_UPDATE_CHANNEL at that file.
 */
class BuildFeed extends Command
{
    protected $signature = 'cms:build-feed
        {version : The release version being published}
        {--dir= : Directory holding the build artifacts (default: cwd)}
        {--repo-url= : Base GitHub repo URL, e.g. https://github.com/owner/repo}
        {--feed= : Path to the releases.json to update (default: <dir>/releases.json)}';

    protected $description = 'Upsert a release into the releases.json update feed';

    public function handle(FeedBuilder $builder): int
    {
        $version = (string) $this->argument('version');
        $dir = rtrim((string) ($this->option('dir') ?: getcwd()), '/');
        $repoUrl = (string) $this->option('repo-url');
        $feedPath = (string) ($this->option('feed') ?: $dir.'/releases.json');

        if ($repoUrl === '') {
            $this->error('--repo-url is required (e.g. https://github.com/owner/repo).');

            return self::FAILURE;
        }

        $entry = $builder->entry($dir, $version, $repoUrl);
        $releases = $builder->merge($this->existingReleases($feedPath), $entry);

        file_put_contents(
            $feedPath,
            json_encode(['releases' => $releases], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
        );

        $this->info("Feed updated: {$feedPath} ({$version}, ".count($releases).' releases).');

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function existingReleases(string $feedPath): array
    {
        if (! is_file($feedPath)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($feedPath), true);
        $releases = $decoded['releases'] ?? null;

        return is_array($releases) ? $releases : [];
    }
}

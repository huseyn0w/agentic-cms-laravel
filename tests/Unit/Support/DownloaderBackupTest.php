<?php

namespace Tests\Unit\Support;

use App\Support\Updater\BackupManager;
use App\Support\Updater\Downloader;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/**
 * Downloader verifies the sha256 before the archive is used; BackupManager
 * snapshots the files an update will overwrite so a failure can be rolled back.
 */
class DownloaderBackupTest extends TestCase
{
    private array $dirs = [];

    protected function tearDown(): void
    {
        foreach ($this->dirs as $dir) {
            $this->rrmdir($dir);
        }
        parent::tearDown();
    }

    private function tmp(string $prefix): string
    {
        $dir = sys_get_temp_dir().'/'.$prefix.'-'.bin2hex(random_bytes(4));
        mkdir($dir, 0777, true);
        $this->dirs[] = $dir;

        return $dir;
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

    public function test_download_writes_the_file_when_the_checksum_matches(): void
    {
        $payload = 'release-bytes-'.bin2hex(random_bytes(8));
        Http::fake(['https://x/rel.tar.gz' => Http::response($payload)]);

        $dest = $this->tmp('dl').'/rel.tar.gz';
        (new Downloader)->download('https://x/rel.tar.gz', hash('sha256', $payload), $dest);

        $this->assertFileExists($dest);
        $this->assertSame($payload, file_get_contents($dest));
    }

    public function test_download_aborts_and_cleans_up_on_checksum_mismatch(): void
    {
        Http::fake(['https://x/rel.tar.gz' => Http::response('actual-bytes')]);

        $dest = $this->tmp('dl').'/rel.tar.gz';

        try {
            (new Downloader)->download('https://x/rel.tar.gz', hash('sha256', 'different'), $dest);
            $this->fail('Expected a checksum mismatch exception.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Checksum mismatch', $e->getMessage());
        }

        $this->assertFileDoesNotExist($dest);
    }

    public function test_backup_snapshots_existing_files_and_restore_puts_them_back(): void
    {
        $target = $this->tmp('target');
        @mkdir($target.'/config', 0777, true);
        file_put_contents($target.'/config/app.php', 'ORIGINAL');

        $backup = $this->tmp('backup');
        $manager = new BackupManager;

        $done = $manager->backupFiles(['config/app.php', 'config/missing.php'], $target, $backup);
        $this->assertSame(['config/app.php'], $done);
        $this->assertSame('ORIGINAL', file_get_contents($backup.'/config/app.php'));

        // Simulate a bad update overwriting the file, then roll back.
        file_put_contents($target.'/config/app.php', 'BROKEN');
        $manager->restoreFiles($backup, $target);

        $this->assertSame('ORIGINAL', file_get_contents($target.'/config/app.php'));
    }

    public function test_database_dump_is_null_on_non_mysql_without_throwing(): void
    {
        // The test suite runs on sqlite, so a dump is skipped, not attempted.
        $this->assertNull((new BackupManager)->dumpDatabase($this->tmp('db')));
    }
}

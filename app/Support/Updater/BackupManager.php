<?php

namespace App\Support\Updater;

use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Snapshots the files an update is about to overwrite (and, best-effort, the
 * database) so a failed update can be rolled back.
 *
 * On shared hosting a full transactional DB rollback isn't guaranteed, so the
 * policy is: migrations are additive-only, plus a file snapshot here and a
 * best-effort mysqldump when the tooling is reachable. Restore copies the file
 * snapshot back over the install.
 */
class BackupManager
{
    /**
     * Copy each existing target file listed in $relPaths into $backupDir,
     * preserving structure. Returns the paths actually backed up.
     *
     * @param  list<string>  $relPaths
     * @return list<string>
     */
    public function backupFiles(array $relPaths, string $targetRoot, string $backupDir): array
    {
        $targetRoot = rtrim($targetRoot, '/');
        $backupDir = rtrim($backupDir, '/');
        $done = [];

        foreach ($relPaths as $rel) {
            $source = $targetRoot.'/'.$rel;

            if (! is_file($source)) {
                continue;
            }

            $dest = $backupDir.'/'.$rel;
            $dir = dirname($dest);
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            copy($source, $dest);
            $done[] = $rel;
        }

        return $done;
    }

    /**
     * Restore a file snapshot back over the install.
     */
    public function restoreFiles(string $backupDir, string $targetRoot): void
    {
        $backupDir = rtrim($backupDir, '/');
        $targetRoot = rtrim($targetRoot, '/');

        if (! is_dir($backupDir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($backupDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                continue;
            }

            $rel = ltrim(str_replace('\\', '/', substr($item->getPathname(), strlen($backupDir))), '/');
            $dest = $targetRoot.'/'.$rel;
            $dir = dirname($dest);
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            copy($item->getPathname(), $dest);
        }
    }

    /**
     * Best-effort database dump via mysqldump. Returns the dump path on success
     * or null when unavailable (non-mysql driver, no proc_open, no mysqldump).
     * Never throws — a missing dump falls back to the additive-only policy.
     */
    public function dumpDatabase(string $backupDir): ?string
    {
        try {
            if (DB::connection()->getDriverName() !== 'mysql') {
                return null;
            }

            $config = DB::connection()->getConfig();
            $dumpPath = rtrim($backupDir, '/').'/database.sql';

            $process = new Process([
                'mysqldump',
                '--host='.($config['host'] ?? '127.0.0.1'),
                '--port='.($config['port'] ?? '3306'),
                '--user='.($config['username'] ?? ''),
                '--password='.($config['password'] ?? ''),
                $config['database'] ?? '',
            ]);
            $process->setTimeout(120);
            $process->run();

            if (! $process->isSuccessful()) {
                return null;
            }

            file_put_contents($dumpPath, $process->getOutput());

            return $dumpPath;
        } catch (Throwable) {
            return null;
        }
    }
}

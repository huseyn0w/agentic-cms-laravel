<?php

namespace App\Console\Commands;

use App\Services\RedirectService;
use Illuminate\Console\Command;

/**
 * Bulk-import managed redirects from a CSV file: each line is
 * `source,target[,status]`. Used to seed the old→new URL map during a migration
 * (e.g. exported WordPress permalinks). Idempotent — re-running updates existing
 * rows by source.
 */
class ImportRedirects extends Command
{
    protected $signature = 'cms:import-redirects {file : Path to a CSV of source,target[,status]}';

    protected $description = 'Bulk-import managed redirects from a CSV file';

    public function handle(RedirectService $redirects): int
    {
        $file = $this->argument('file');

        if (! is_file($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        $handle = fopen($file, 'r');
        if ($handle === false) {
            $this->error("Cannot read: {$file}");

            return self::FAILURE;
        }

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        $count = $redirects->import($rows);
        $this->info("Imported {$count} redirect(s).");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Support\Updater\UpdateException;
use App\Support\Updater\UpdateService;
use Illuminate\Console\Command;

/**
 * Apply a core update from the CLI (the same UpdateService the admin button
 * uses). `--check` only reports whether an update is available. Useful on hosts
 * where the operator prefers SSH, and for the scheduled check.
 */
class RunUpdate extends Command
{
    protected $signature = 'cms:update {--check : Only report whether an update is available}';

    protected $description = 'Check for and apply a core update';

    public function handle(UpdateService $service): int
    {
        $release = $service->checkForUpdate();

        if ($release === null) {
            $this->info('Core is up to date (v'.cms_version().').');

            return self::SUCCESS;
        }

        $this->info('Update available: '.$release['version'].' (current v'.cms_version().').');

        if ($this->option('check')) {
            return self::SUCCESS;
        }

        try {
            $audit = $service->update($release);
            $this->info('Updated to '.$audit->to_version.'.');

            return self::SUCCESS;
        } catch (UpdateException $e) {
            $this->error('Update failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}

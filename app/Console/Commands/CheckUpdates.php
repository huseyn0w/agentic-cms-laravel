<?php

namespace App\Console\Commands;

use App\Support\Updater\UpdateService;
use Illuminate\Console\Command;

/**
 * Refresh the "is an update available?" flag from the feed and cache it. Run on
 * a schedule (hourly/daily per config) so the admin sees a banner without a live
 * feed call on every page. A no-op-ish network read; safe to run often.
 */
class CheckUpdates extends Command
{
    protected $signature = 'cms:check-updates';

    protected $description = 'Refresh the cached core-update availability from the feed';

    public function handle(UpdateService $service): int
    {
        $release = $service->refreshAvailability();

        $this->info($release === null
            ? 'Core is up to date (v'.cms_version().').'
            : 'Update available: '.$release['version'].'.');

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\Seo\SsrProcess;
use Illuminate\Console\Command;

/**
 * Starts the server-side renderer if it is not answering. Scheduled every five
 * minutes (App\Console\Kernel::schedule), so it must be a no-op when SSR is off
 * or already up. The renderer is a plain Node process; nothing here depends on
 * the hosting panel.
 */
class KeepSsrRunning extends Command
{
    protected $signature = 'ssr:keepalive';

    protected $description = 'Start the server-side renderer if it is not answering';

    public function handle(SsrProcess $ssr): int
    {
        if (! $ssr->isEnabled()) {
            $this->info('Server-side rendering is off; nothing to do.');

            return self::SUCCESS;
        }

        if ($ssr->isRunning()) {
            $this->info('The renderer is answering.');

            return self::SUCCESS;
        }

        if (! $ssr->start()) {
            $this->warn('No node binary or no bundle - pages will be client-rendered.');

            return self::SUCCESS;
        }

        $this->info('Started the renderer.');

        return self::SUCCESS;
    }
}

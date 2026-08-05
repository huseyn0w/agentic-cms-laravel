<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Flip due scheduled posts to published. Cheap indexed query; runs often
        // so a scheduled post goes live close to its time.
        $schedule->command('posts:publish-due')->everyMinute()->withoutOverlapping();

        // The server-side renderer is a plain Node process started by the deploy,
        // which covers everything but a server reboot. This brings it back on its
        // own, so nothing about SSR depends on the hosting panel - no Node app, no
        // subdomain, and no publicly reachable render endpoint. A no-op when SSR
        // is off or already answering.
        $schedule->command('ssr:keepalive')->everyFiveMinutes()->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

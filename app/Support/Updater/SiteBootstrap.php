<?php

namespace App\Support\Updater;

use Illuminate\Contracts\Foundation\Application;

/**
 * Wires a fork's site zone into the core boot process.
 *
 * The site zone (app/Site) is never overwritten by a core update. A fork opts
 * in by adding App\Site\Providers\SiteServiceProvider (ship the .stub as .php).
 * Core registers it only when the class exists, so a stock install with no site
 * provider boots unchanged.
 */
class SiteBootstrap
{
    public const PROVIDER = 'App\\Site\\Providers\\SiteServiceProvider';

    public static function register(Application $app): void
    {
        if (class_exists(self::PROVIDER)) {
            $app->register(self::PROVIDER);
        }
    }
}

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Core version
    |--------------------------------------------------------------------------
    |
    | The version of the Agentic CMS core itself, independent of the Laravel
    | framework version. The WordPress-style updater compares this value with
    | the release feed to decide whether an update is available. Bumped by the
    | CI release job on a `v*` tag.
    |
    */

    'version' => '1.0.0',

    /*
    |--------------------------------------------------------------------------
    | Update channel
    |--------------------------------------------------------------------------
    |
    | Settings that drive the in-admin "update core" button and the background
    | version check. A fork on tier-1 (data/token theming) points `channel` at
    | the upstream core releases; a tier-2 fork (code-level theme) points it at
    | its own CI-built release feed.
    |
    */

    'update' => [

        // Feed URL that lists available releases (GitHub Releases API or a
        // committed releases.json). Empty disables update checks.
        'channel' => env('CMS_UPDATE_CHANNEL', ''),

        // Public key used to verify the release signature (minisign/GPG). A
        // signature is mandatory in production; unsigned releases are refused.
        'public_key' => env('CMS_UPDATE_PUBLIC_KEY', ''),

        // When true (and composer is reachable on the host) the updater runs
        // `composer install --no-dev` after extracting, instead of trusting
        // the vendor/ shipped inside the release tarball. Off by default so
        // updates work on hosts where composer is disabled or slow.
        'install_composer' => (bool) env('CMS_UPDATE_INSTALL_COMPOSER', false),

        // How often the background job checks the feed for a newer version.
        // One of: 'hourly', 'daily'.
        'check_schedule' => env('CMS_UPDATE_CHECK_SCHEDULE', 'daily'),
    ],

];

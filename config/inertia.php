<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Server Side Rendering
    |--------------------------------------------------------------------------
    |
    | Without SSR a crawler that does not run JavaScript sees an empty <div>
    | and nothing else. The public pages carry the words that get indexed, so
    | they are rendered on a small Node process (`node bootstrap/ssr/ssr.js`)
    | that this app talks to over HTTP on 127.0.0.1. If that process is not
    | running, Inertia catches the failure and serves the ordinary
    | client-rendered page, so the worst case is exactly today's behaviour
    | rather than an error.
    |
    */

    'ssr' => [

        /*
         * The per-request decision, written by EnableSsrOnPublicRoutes on the
         * way in and read by Inertia's HttpGateway on the way out.
         *
         * Hardcoded false here (not env('INERTIA_SSR_ENABLED') as the package
         * default does) because it is an output, not a setting: SSR is off for
         * every request until the middleware turns it on for an allow-listed
         * public route. The switch a human turns is `ssr.public.enabled` below.
         *
         * This also keeps the admin panel off SSR entirely: with the bundle
         * present, the package default of true would spend a refused TCP
         * connection on every admin page whenever the Node process is down.
         */
        'enabled' => false,

        'runtime' => env('INERTIA_SSR_RUNTIME', 'node'),

        'ensure_runtime_exists' => (bool) env('INERTIA_SSR_ENSURE_RUNTIME_EXISTS', false),

        'url' => env('INERTIA_SSR_URL', 'http://127.0.0.1:13714'),

        'ensure_bundle_exists' => (bool) env('INERTIA_SSR_ENSURE_BUNDLE_EXISTS', true),

        'throw_on_error' => (bool) env('INERTIA_SSR_THROW_ON_ERROR', false),

        'public' => [

            /*
             * The master switch. Off by default so a deploy that ships the SSR
             * bundle without a Node process behind it does not spend a refused
             * TCP connection on every page view. Turn it on once the Node
             * process is running on the host.
             */
            'enabled' => (bool) env('INERTIA_SSR_ENABLED', false),

            /*
             * Override only if the host keeps Node somewhere unusual. Left
             * empty, SsrProcess picks the newest /opt/alt/alt-nodejs* runtime
             * (CloudLinux, where Hostinger keeps Node off PATH), then whatever
             * `node` is on PATH.
             */
            'node_binary' => env('INERTIA_SSR_NODE'),

            /*
             * The only routes that get server-rendered, by name.
             *
             * An allow-list rather than "everything except admin", because the
             * cost of the two mistakes is not symmetrical: forgetting to add a
             * new public page costs some SEO, forgetting to exclude a new
             * private one renders a signed-in user's page in a shared Node
             * process. Empty until the public controllers are migrated to
             * Inertia; each public route joins the list as it lands.
             */
            'routes' => [
                // Public pages on Inertia. Each joins the list as its controller
                // moves off Blade; SSR renders only these for crawlers.
                'front_pages', // homepage (home template) — other page templates
                // still Blade until their slices, harmless here.
            ],

        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    */

    'pages' => [

        'ensure_pages_exist' => false,

        'paths' => [

            resource_path('js/pages'),

        ],

        'extensions' => [

            'js',
            'jsx',
            'svelte',
            'ts',
            'tsx',
            'vue',

        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Testing
    |--------------------------------------------------------------------------
    |
    | When using `assertInertia`, the assertion locates the component as a file
    | relative to `pages.paths` with any of `pages.extensions`.
    |
    */

    'testing' => [

        'ensure_pages_exist' => true,

    ],

    /*
    |--------------------------------------------------------------------------
    | Expose Shared Prop Keys
    |--------------------------------------------------------------------------
    |
    | Ships the NAMES of the props registered through Inertia::share so the
    | client can carry them across an instant visit. Names only, never values.
    |
    */

    'expose_shared_prop_keys' => true,

    /*
    |--------------------------------------------------------------------------
    | History
    |--------------------------------------------------------------------------
    */

    'history' => [

        'encrypt' => (bool) env('INERTIA_ENCRYPT_HISTORY', false),

    ],

];

import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    plugins: [
        laravel({
            input: [
                // Inertia + React entry (the whole UI is React now).
                'resources/js/app.tsx',
                // Stylesheets loaded by the Inertia root templates.
                'resources/css/app.css',
                'resources/css/admin.css',
            ],
            // Inertia SSR entry — built by `vite build --ssr` into
            // bootstrap/ssr/ssr.js and run by `php artisan inertia:start-ssr`.
            // Scaffolded in Phase 4; not enabled on any route yet.
            ssr: 'resources/js/ssr.tsx',
            refresh: true,
        }),
        react(),
    ],
    // Bundle everything into the standalone Node SSR build so it runs without a
    // node_modules resolution step in production.
    ssr: {
        noExternal: true,
    },
});

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
                // Inertia + React entry (new stack).
                'resources/js/app.tsx',
                // Legacy Blade/Alpine assets — kept while the strangler migration
                // runs so the pre-Inertia pages keep working.
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/admin.css',
                'resources/js/admin.js',
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

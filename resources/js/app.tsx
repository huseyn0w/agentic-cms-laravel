import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { initI18n, syncI18n } from '@/lib/i18n';
import type { SharedProps } from '@/lib/types';

const appName = import.meta.env.VITE_APP_NAME ?? 'Agentic CMS';

createInertiaApp({
    title: (title) => (title ? `${title} — ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            // Exclude *.test.tsx: co-located test files under pages/ must not
            // ship in the production bundle (they pull in vitest/RTL code).
            import.meta.glob(['./pages/**/*.tsx', '!./pages/**/*.test.tsx']),
        ),
    setup({ el, App, props }) {
        const shared = props.initialPage.props as unknown as SharedProps;
        initI18n(shared.locale.current, shared.messages);

        router.on('navigate', (event) => {
            const next = event.detail.page.props as unknown as SharedProps;
            if (next.messages) {
                syncI18n(next.locale.current, next.messages);
            }
        });

        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: '#4f46e5',
    },
});

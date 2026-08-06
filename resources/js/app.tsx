import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { ComponentType } from 'react';
import { createRoot } from 'react-dom/client';
import { initI18n, syncI18n } from '@/lib/i18n';
import { ErrorBoundary } from '@/components/ErrorBoundary';
import type { SharedProps } from '@/lib/types';

const appName = import.meta.env.VITE_APP_NAME ?? 'Agentic CMS';

createInertiaApp({
    title: (title) => (title ? `${title} — ${appName}` : appName),
    // @ts-expect-error laravel-vite-plugin resolves modules as React's
    // ComponentType, which @inertiajs/react's internal ReactComponent type does
    // not structurally accept — runtime-correct (build + tests pass), upstream
    // type-def skew only.
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            // Exclude *.test.tsx: co-located test files under pages/ must not
            // ship in the production bundle (they pull in vitest/RTL code).
            import.meta.glob<{ default: ComponentType }>([
                './pages/**/*.tsx',
                '!./pages/**/*.test.tsx',
            ]),
        ),
    // @ts-expect-error @inertiajs/react types `el` as HTMLElement | null; the
    // callback handles the null case below. Upstream type-def skew only.
    setup({ el, App, props }) {
        const shared = props.initialPage.props as unknown as SharedProps;
        initI18n(shared.locale.current, shared.messages);

        router.on('navigate', (event) => {
            const next = event.detail.page.props as unknown as SharedProps;
            if (next.messages) {
                syncI18n(next.locale.current, next.messages);
            }
        });

        if (el) {
            createRoot(el).render(
                <ErrorBoundary>
                    <App {...props} />
                </ErrorBoundary>,
            );
        }
    },
    progress: {
        // Vercel-blue top loading bar; show quickly on real navigations.
        // Cache-hit (prefetched) visits resolve instantly and skip it by design.
        color: '#0070f3',
        delay: 120,
        showSpinner: false,
    },
});

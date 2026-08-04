import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { ComponentType } from 'react';
import ReactDOMServer from 'react-dom/server';
import { initI18n } from '@/lib/i18n';
import { ErrorBoundary } from '@/components/ErrorBoundary';
import type { SharedProps } from '@/lib/types';

const appName = import.meta.env.VITE_APP_NAME ?? 'Agentic CMS';

// Server-side render entry. Mirrors resources/js/app.tsx (same page resolver,
// same ErrorBoundary wrapper, same per-request i18n init) but renders to a
// string via Node for public-page SEO. Enabled per-route in Phase 4; building
// this bundle (`vite build --ssr`) validates the toolchain ahead of that.
//
// NOTE (Phase 4): i18next is a module singleton, so under concurrent SSR the
// active locale can race across requests. Address when public pages actually
// SSR (e.g. a request-scoped i18n instance) — no public route is SSR'd yet.
createServer((page) =>
    createInertiaApp({
        page,
        // @ts-expect-error With `page` set this is the SSR overload (render +
        // element-returning setup), but @inertiajs/react's types resolve to the
        // client overload here (render: undefined). Runtime-correct — the SSR
        // bundle builds and renders; upstream type-def skew only.
        render: (node) => ReactDOMServer.renderToString(node),
        title: (title) => (title ? `${title} — ${appName}` : appName),
        // @ts-expect-error laravel-vite-plugin resolves modules as React's
        // ComponentType, which @inertiajs/react's internal ReactComponent type
        // does not structurally accept — runtime-correct, upstream type skew.
        resolve: (name) =>
            resolvePageComponent(
                `./pages/${name}.tsx`,
                import.meta.glob<{ default: ComponentType }>([
                    './pages/**/*.tsx',
                    '!./pages/**/*.test.tsx',
                ]),
            ),
        // @ts-expect-error createInertiaApp's setup is typed for the client
        // (el: HTMLElement | null); the SSR path passes no el and returns the
        // element to render. Runtime-correct, upstream type-def skew only.
        setup: ({ App, props }) => {
            const shared = props.initialPage.props as unknown as SharedProps;
            initI18n(shared.locale.current, shared.messages);

            return (
                <ErrorBoundary>
                    <App {...props} />
                </ErrorBoundary>
            );
        },
    }),
);

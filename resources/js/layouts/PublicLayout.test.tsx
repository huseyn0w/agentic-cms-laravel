import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({
    Link: ({ children, prefetch, cacheFor, ...p }: any) => <a data-inertia="true" {...p}>{children}</a>,
    usePage: () => ({ props: {} }),
    useForm: (initial: any) => ({ data: initial ?? {}, setData: () => undefined, post: () => undefined, processing: false, reset: () => undefined, errors: {} }),
}));

import { PublicLayout } from './PublicLayout';
import type { Shell } from './PublicLayout';

function makeShell(overrides: Partial<Shell> = {}): Shell {
    return {
        wordmark: 'AgenticCms-Laravel',
        homeUrl: 'https://example.test',
        logoUrl: null,
        searchUrl: 'https://example.test/search',
        currentLang: 'EN',
        menu: [{ title: 'About', url: 'https://example.test/about', internal: false, children: [] }],
        languages: [{ code: 'EN', url: 'https://example.test', title: 'English', icon: null, current: true }],
        general: { websiteName: 'AgenticCms-Laravel', membership: true },
        site: { copyright: null, linkedinUrl: null, githubUrl: null },
        auth: { user: null, canSeeAdmin: false, loginUrl: '/login', registerUrl: '/register' },
        ...overrides,
    };
}

describe('PublicLayout', () => {
    it('renders non-current language links as prefetching Inertia links', () => {
        render(
            <PublicLayout
                shell={makeShell({
                    languages: [
                        { code: 'EN', url: 'https://example.test', title: 'English', icon: null, current: true },
                        { code: 'RU', url: 'https://example.test/ru', title: 'Russian', icon: null, current: false },
                    ],
                })}
            >
                content
            </PublicLayout>,
        );
        const switcher = screen.getByTestId('locale-switcher');
        // The non-current language is an Inertia <Link> (mock stamps data-inertia).
        const ru = switcher.querySelector('[data-testid="lang-ru"]');
        expect(ru).not.toBeNull();
        expect(ru?.getAttribute('data-inertia')).toBe('true');
        // The current language is not a navigable link.
        const en = switcher.querySelector('[data-testid="lang-en"]');
        expect(en?.getAttribute('data-inertia')).not.toBe('true');
    });

    it('opens a focus-trapped modal drawer from the mobile button', () => {
        render(<PublicLayout shell={makeShell()}>content</PublicLayout>);

        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();

        fireEvent.click(screen.getByTestId('mobile-menu-button'));

        const drawer = screen.getByRole('dialog');
        expect(drawer).toHaveAttribute('aria-modal', 'true');
        expect(drawer).toHaveAttribute('id', 'mobile-drawer');
    });

    it('shows the register link only when membership is on', () => {
        const { rerender } = render(<PublicLayout shell={makeShell({ general: { websiteName: 'x', membership: true } })}>c</PublicLayout>);
        expect(screen.getByRole('link', { name: 'Register' })).toHaveAttribute('href', '/register');

        rerender(<PublicLayout shell={makeShell({ general: { websiteName: 'x', membership: false } })}>c</PublicLayout>);
        expect(screen.queryByRole('link', { name: 'Register' })).not.toBeInTheDocument();
    });

    it('shows admin + logout for an authenticated admin, not login', () => {
        render(
            <PublicLayout
                shell={makeShell({
                    auth: { user: { name: 'Jane' }, canSeeAdmin: true, adminUrl: '/admin', logoutUrl: '/logout' },
                })}
            >
                c
            </PublicLayout>,
        );

        expect(screen.getByRole('link', { name: 'Admin' })).toHaveAttribute('href', '/admin');
        expect(screen.getByRole('link', { name: 'Log out' })).toBeInTheDocument();
        expect(screen.queryByRole('link', { name: 'Log in' })).not.toBeInTheDocument();
    });

    it('renders legal footer links when the shell provides them', () => {
        render(
            <PublicLayout
                shell={makeShell({
                    legalLinks: [
                        { title: 'Imprint', url: 'https://example.test/impressum' },
                        { title: 'Privacy Policy', url: 'https://example.test/datenschutz' },
                    ],
                })}
            >
                c
            </PublicLayout>,
        );

        const legal = screen.getByTestId('footer-legal');
        expect(legal).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Imprint' })).toHaveAttribute('href', 'https://example.test/impressum');
        expect(screen.getByRole('link', { name: 'Privacy Policy' })).toHaveAttribute('href', 'https://example.test/datenschutz');
    });
});

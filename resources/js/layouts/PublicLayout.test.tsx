import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({
    Link: ({ children, prefetch, cacheFor, ...p }: any) => <a {...p}>{children}</a>,
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
});

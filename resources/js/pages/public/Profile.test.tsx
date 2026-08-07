import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
vi.mock('@inertiajs/react', () => ({
    Link: ({ children, prefetch, cacheFor, ...p }: any) => <a {...p}>{children}</a>,
    usePage: () => ({ props: {} }),
    useForm: (initial: any) => ({ data: initial ?? {}, setData: () => undefined, post: () => undefined, processing: false, reset: () => undefined, errors: {} }),
}));

import Profile from './Profile';
import type { Shell } from '@/layouts/PublicLayout';

const shell: Shell = {
    wordmark: 'AgenticCms-Laravel',
    homeUrl: 'https://example.test',
    logoUrl: null,
    searchUrl: 'https://example.test/search',
    currentLang: 'EN',
    menu: [],
    languages: [],
    general: { websiteName: 'AgenticCms-Laravel', membership: true },
    site: { copyright: null, linkedinUrl: null, githubUrl: null },
    auth: { user: null, canSeeAdmin: false, loginUrl: '/login', registerUrl: '/register' },
};

const profile = (over: Record<string, unknown> = {}) => ({
    displayName: 'Jane Doe',
    username: 'jane',
    avatar: '/a.png',
    role: 'Author',
    gender: 'female',
    aboutMe: 'I write things.',
    email: 'jane@example.test',
    country: 'Germany',
    city: 'Berlin',
    socials: [{ label: 'LinkedIn', url: 'https://linkedin.test/jane' }],
    isOwnProfile: false,
    editUrl: '/profile/edit',
    ...over,
});

describe('public Profile', () => {
    it('renders identity, details and socials', () => {
        render(<Profile shell={shell} profile={profile() as any} />);
        expect(screen.getByRole('heading', { level: 1, name: 'Jane Doe' })).toBeInTheDocument();
        expect(screen.getByText('@jane')).toBeInTheDocument();
        expect(screen.getByText('I write things.')).toBeInTheDocument();
        expect(screen.getByText('jane@example.test')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: /LinkedIn/ })).toHaveAttribute('href', 'https://linkedin.test/jane');
    });

    // tr(k, fallback) returns the fallback when the mocked t() echoes the key.
    it('shows the edit link only on your own profile', () => {
        const { rerender } = render(<Profile shell={shell} profile={profile({ isOwnProfile: false }) as any} />);
        expect(screen.queryByText('Edit profile')).not.toBeInTheDocument();

        rerender(<Profile shell={shell} profile={profile({ isOwnProfile: true }) as any} />);
        expect(screen.getByRole('link', { name: 'Edit profile' })).toHaveAttribute('href', '/profile/edit');
    });
});

import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({
    Link: ({ children, prefetch, cacheFor, ...p }: any) => <a {...p}>{children}</a>,
    usePage: () => ({ props: {} }),
    useForm: (initial: any) => ({ data: initial ?? {}, setData: () => undefined, post: () => undefined, processing: false, reset: () => undefined, errors: {} }),
}));

import Page from './Page';
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

describe('public Page', () => {
    it('renders the title, lead and prose content', () => {
        render(
            <Page
                shell={shell}
                page={{ title: 'About us', lead: 'Who we are', content: '<p>Company story.</p>' }}
                crumbs={[{ label: 'Home', url: 'https://example.test' }, { label: 'About us', url: null }]}
            />,
        );
        expect(screen.getByRole('heading', { level: 1, name: 'About us' })).toBeInTheDocument();
        expect(screen.getByText('Who we are')).toBeInTheDocument();
        expect(screen.getByText('Company story.')).toBeInTheDocument();
    });
});

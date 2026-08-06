import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({
    Link: ({ children, prefetch, cacheFor, preserveScroll, ...p }: any) => <a {...p}>{children}</a>,
}));

import Archive from './Archive';
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

const archive = (over: Record<string, unknown> = {}) => ({
    title: 'Announcements',
    crumbs: [
        { label: 'Home', url: 'https://example.test' },
        { label: 'Announcements', url: null },
    ],
    posts: [
        { title: 'First post', url: 'https://example.test/posts/first', excerpt: 'Excerpt', image: '/a.png', date: '2026-01-01' },
        { title: 'Second post', url: 'https://example.test/posts/second', excerpt: '', image: '/b.png', date: '2026-01-02' },
    ],
    currentPage: 1,
    lastPage: 3,
    total: 7,
    pageBaseUrl: 'https://example.test/category/announcements',
    emptyText: 'No posts found',
    ...over,
});

describe('public Archive', () => {
    it('renders the title, breadcrumb and post cards', () => {
        render(<Archive shell={shell} archive={archive() as any} />);
        expect(screen.getByRole('heading', { level: 1, name: 'Announcements' })).toBeInTheDocument();
        expect(screen.getByRole('link', { name: /First post/ })).toHaveAttribute('href', 'https://example.test/posts/first');
        expect(screen.getAllByTestId('archive-post')).toHaveLength(2);
    });

    it('renders pagination links to the display-pages route', () => {
        render(<Archive shell={shell} archive={archive() as any} />);
        const page2 = screen.getByRole('link', { name: '2' });
        expect(page2).toHaveAttribute('href', 'https://example.test/category/announcements/page/2');
        // current page is marked
        expect(screen.getByRole('link', { name: '1' })).toHaveAttribute('aria-current', 'page');
    });

    it('hides pagination when there is a single page', () => {
        render(<Archive shell={shell} archive={archive({ lastPage: 1 }) as any} />);
        expect(screen.queryByLabelText('Pagination')).not.toBeInTheDocument();
    });

    it('shows the empty state when there are no posts', () => {
        render(<Archive shell={shell} archive={archive({ posts: [], lastPage: 1 }) as any} />);
        expect(screen.getByTestId('archive-empty')).toHaveTextContent('No posts found');
    });
});

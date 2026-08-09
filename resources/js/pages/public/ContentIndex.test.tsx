import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
vi.mock('@inertiajs/react', () => ({
    Link: ({ children, prefetch, cacheFor, ...p }: any) => <a {...p}>{children}</a>,
    usePage: () => ({ props: {} }),
    useForm: (initial: any) => ({ data: initial ?? {}, setData: () => undefined, post: () => undefined, processing: false, reset: () => undefined, errors: {} }),
}));

import ContentIndex from './ContentIndex';
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

const fields: Array<{ name: string; label: string; type: string; options: Record<string, string> }> = [
    { name: 'title', label: 'Title', type: 'text', options: {} },
    { name: 'category', label: 'Category', type: 'text', options: {} },
    { name: 'excerpt', label: 'Excerpt', type: 'textarea', options: {} },
    { name: 'thumbnail', label: 'Image', type: 'image', options: {} },
    { name: 'external_url', label: 'Link', type: 'url', options: {} },
];

const base = {
    shell,
    heading: 'Projects',
    slug: 'projects',
    fields,
    hasDetail: true,
    detailBase: 'https://example.test/projects',
    emptyText: 'Nothing here yet.',
    items: [
        { id: 7, title: 'Shopify Editions', category: 'Shopify', excerpt: 'A case study', thumbnail: 'https://cdn.test/a.png', external_url: 'https://shopify.com' },
        { id: 8, title: 'Second', category: null, excerpt: null, thumbnail: null, external_url: null },
    ],
};

describe('public ContentIndex', () => {
    it('renders the heading and a card per item', () => {
        render(<ContentIndex {...base} />);
        expect(screen.getByRole('heading', { level: 1, name: 'Projects' })).toBeInTheDocument();
        expect(screen.getAllByTestId('content-card')).toHaveLength(2);
    });

    it('links each item title to its detail page and shows meta', () => {
        render(<ContentIndex {...base} />);
        expect(screen.getByRole('link', { name: 'Shopify Editions' })).toHaveAttribute('href', 'https://example.test/projects/7');
        expect(screen.getByText('Shopify')).toBeInTheDocument();
    });

    it('links to the URL field when the type has no detail page', () => {
        render(<ContentIndex {...base} hasDetail={false} />);
        expect(screen.getByRole('link', { name: 'Shopify Editions' })).toHaveAttribute('href', 'https://shopify.com');
    });

    it('shows the empty state when there are no items', () => {
        render(<ContentIndex {...base} items={[]} />);
        expect(screen.getByTestId('content-empty')).toHaveTextContent('Nothing here yet.');
    });
});

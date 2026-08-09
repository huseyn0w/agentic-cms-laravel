import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
vi.mock('@inertiajs/react', () => ({
    Link: ({ children, prefetch, cacheFor, ...p }: any) => <a {...p}>{children}</a>,
    usePage: () => ({ props: {} }),
    useForm: (initial: any) => ({ data: initial ?? {}, setData: () => undefined, post: () => undefined, processing: false, reset: () => undefined, errors: {} }),
}));

import ContentDetail from './ContentDetail';
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
    { name: 'content', label: 'Description', type: 'richtext', options: {} },
    { name: 'thumbnail', label: 'Image', type: 'image', options: {} },
    { name: 'external_url', label: 'Link', type: 'url', options: {} },
    { name: 'status', label: 'Status', type: 'select', options: { published: 'Published', draft: 'Draft' } },
];

const base = {
    shell,
    slug: 'projects',
    title: 'Shopify Editions',
    fields,
    indexUrl: 'https://example.test/projects',
    indexLabel: 'Projects',
    item: {
        id: 7,
        title: 'Shopify Editions',
        category: 'Shopify',
        content: '<p>The case study body</p>',
        thumbnail: 'https://cdn.test/a.png',
        external_url: 'https://shopify.com',
        status: 'published',
    },
};

describe('public ContentDetail', () => {
    it('renders the title and a back link to the index', () => {
        render(<ContentDetail {...base} />);
        expect(screen.getByRole('heading', { level: 1, name: 'Shopify Editions' })).toBeInTheDocument();
        expect(screen.getByTestId('content-back')).toHaveAttribute('href', 'https://example.test/projects');
        expect(screen.getByTestId('content-back')).toHaveTextContent('Projects');
    });

    it('renders richtext body and maps a select value to its label', () => {
        render(<ContentDetail {...base} />);
        expect(screen.getByTestId('content-body')).toContainHTML('<p>The case study body</p>');
        expect(screen.getByTestId('content-meta')).toHaveTextContent('Published');
        expect(screen.getByTestId('content-meta')).toHaveTextContent('Shopify');
    });

    it('renders the URL field as an external link', () => {
        render(<ContentDetail {...base} />);
        expect(screen.getByRole('link', { name: /Link/ })).toHaveAttribute('href', 'https://shopify.com');
    });
});

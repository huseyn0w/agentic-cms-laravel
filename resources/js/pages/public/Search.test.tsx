import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

let sharedProps: any = { errors: {}, flash: {} };

vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
vi.mock('@inertiajs/react', () => ({
    Link: ({ children, prefetch, cacheFor, ...p }: any) => <a {...p}>{children}</a>,
    usePage: () => ({ props: sharedProps }),
}));

import Search from './Search';
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

const props = (over: Record<string, unknown> = {}) => ({
    shell,
    title: 'Search',
    action: 'https://example.test/search',
    csrfToken: 'test-token',
    captchaHtml: '',
    results: null,
    ...over,
});

const results = (over: Record<string, unknown> = {}) => ({
    query: 'Laravel',
    type: 'tag',
    total: 1,
    currentPage: 1,
    lastPage: 1,
    items: [{ label: 'laravel', url: 'https://example.test/tag/laravel' }],
    pageBaseUrl: 'https://example.test/search/query/Laravel/filter/tag',
    ...over,
});

describe('public Search', () => {
    it('renders the form with a CSRF token, query field and filter select', () => {
        sharedProps = { errors: {}, flash: {} };
        const { container } = render(<Search {...(props() as any)} />);
        const form = container.querySelector('form')!;
        expect(form).toHaveAttribute('action', 'https://example.test/search');
        expect(form.querySelector('input[name="_token"]')).toHaveAttribute('value', 'test-token');
        expect(container.querySelector('input[name="query"]')).toBeInTheDocument();
        expect(container.querySelector('select[name="filter"]')).toBeInTheDocument();
    });

    it('renders shaped results with links to each entity', () => {
        sharedProps = { errors: {}, flash: {} };
        render(<Search {...(props({ results: results() }) as any)} />);
        const links = screen.getAllByTestId('search-result');
        expect(links).toHaveLength(1);
        expect(links[0]).toHaveAttribute('href', 'https://example.test/tag/laravel');
        expect(links[0]).toHaveTextContent('laravel');
    });

    it('renders the empty state when a search returns nothing', () => {
        sharedProps = { errors: {}, flash: {} };
        render(<Search {...(props({ results: results({ total: 0, items: [] }) }) as any)} />);
        expect(screen.getByTestId('search-empty')).toBeInTheDocument();
        expect(screen.queryByTestId('search-result')).not.toBeInTheDocument();
    });

    it('renders pagination links when there is more than one page', () => {
        sharedProps = { errors: {}, flash: {} };
        render(<Search {...(props({ results: results({ lastPage: 3 }) }) as any)} />);
        const nav = screen.getByRole('navigation', { name: 'Pagination' });
        const pages = nav.querySelectorAll('a');
        expect(pages).toHaveLength(3);
        expect(pages[1]).toHaveAttribute('href', 'https://example.test/search/query/Laravel/filter/tag/page/2');
    });

    it('surfaces validation errors from shared props', () => {
        sharedProps = { errors: { query: 'The query field is required.' }, flash: {} };
        render(<Search {...(props() as any)} />);
        expect(screen.getByRole('alert')).toHaveTextContent('The query field is required.');
    });
});

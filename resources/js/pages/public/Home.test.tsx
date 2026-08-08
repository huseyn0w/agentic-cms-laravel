import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({
    Link: ({ children, prefetch, cacheFor, ...p }: any) => <a {...p}>{children}</a>,
    usePage: () => ({ props: {} }),
    useForm: (initial: any) => ({ data: initial ?? {}, setData: () => undefined, post: () => undefined, processing: false, reset: () => undefined, errors: {} }),
}));

import Home from './Home';
import type { Shell } from '@/layouts/PublicLayout';

const shell: Shell = {
    wordmark: 'AgenticCms-Laravel',
    homeUrl: 'https://example.test',
    logoUrl: null,
    searchUrl: 'https://example.test/search',
    currentLang: 'EN',
    menu: [{ title: 'About', url: 'https://example.test/about', internal: true, children: [] }],
    languages: [
        { code: 'EN', url: 'https://example.test', title: 'English', icon: null, current: true },
        { code: 'RU', url: 'https://example.test/ru', title: 'Russian', icon: null, current: false },
    ],
    general: { websiteName: 'AgenticCms-Laravel', membership: true },
    site: { copyright: '© 2026', linkedinUrl: 'https://linkedin.test', githubUrl: null },
    auth: { user: null, canSeeAdmin: false, loginUrl: '/login', registerUrl: '/register' },
};

const baseProps = {
    shell,
    page: { title: 'Home' },
    hero: { headline: '<span>Build faster</span>', background: null },
    postsSection: {
        headline: 'Latest',
        description: 'Recent writing',
        posts: [
            { title: 'First post', url: 'https://example.test/posts/first', thumbnail: '/img/a.jpg', coverSeed: 'first', excerpt: 'An excerpt', date: '01 Jan 2026' },
        ],
    },
    about: {
        headline: 'About us',
        description: 'Who we are',
        body: '<p>Long form.</p>',
        authors: [{ name: 'Jane Doe', image: '/img/jane.jpg', position: 'Editor', linkedinUrl: 'https://linkedin.test/jane', linkedinBlank: true }],
    },
};

describe('public Home', () => {
    it('renders the hero headline and page eyebrow', () => {
        render(<Home {...baseProps} />);
        expect(screen.getByText('Build faster')).toBeInTheDocument();
        expect(screen.getByText('Home')).toBeInTheDocument();
    });

    it('renders the posts-from-category cards as full-page links', () => {
        render(<Home {...baseProps} />);
        const link = screen.getByRole('link', { name: /First post/i });
        expect(link).toHaveAttribute('href', 'https://example.test/posts/first');
        expect(screen.getByText('01 Jan 2026')).toBeInTheDocument();
    });

    it('renders the about section and author cards', () => {
        render(<Home {...baseProps} />);
        expect(screen.getByText('About us')).toBeInTheDocument();
        expect(screen.getByText('Jane Doe')).toBeInTheDocument();
        expect(screen.getByText('Editor')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: /Jane Doe on LinkedIn/i })).toHaveAttribute('target', '_blank');
    });

    it('hides the posts section when there are no posts', () => {
        render(<Home {...baseProps} postsSection={{ headline: 'Latest', description: null, posts: [] }} />);
        expect(screen.queryByText('Latest')).not.toBeInTheDocument();
    });

    it('renders the shell header and footer wordmark', () => {
        render(<Home {...baseProps} />);
        expect(screen.getByTestId('public-header')).toBeInTheDocument();
        expect(screen.getByTestId('public-footer')).toBeInTheDocument();
    });
});

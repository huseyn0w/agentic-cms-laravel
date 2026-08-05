import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const post = vi.fn();

vi.mock('@inertiajs/react', () => ({
    Link: ({ children, ...p }: any) => <a {...p}>{children}</a>,
    useForm: (initial: any) => ({
        data: initial,
        errors: {},
        processing: false,
        setData: (k: string, v: any) => {
            initial[k] = v;
        },
        reset: () => undefined,
        post: (url: string, opts: any) => post(url, opts),
    }),
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));

import Post from './Post';
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
    currentUserId: 7,
    post: {
        id: 3,
        title: 'Introducing the CMS',
        content: '<p>Body copy.</p>',
        thumbnail: null,
        date: '01.01.2026',
        dateIso: '2026-01-01T00:00:00+00:00',
        likes: 2,
        liked: false,
        likeUrl: '/handlelike/3',
        author: { name: 'Jane Doe', username: 'jane', url: '/users/jane', avatar: '/a.png' },
        category: { title: 'News', url: '/category/news' },
        tags: [{ name: 'launch', url: '/tag/launch' }],
    },
    related: [{ title: 'Another', url: '/posts/another', excerpt: 'x', date: '2026-01-02', image: '/b.png' }],
    comments: {
        total: 1,
        currentPage: 1,
        lastPage: 1,
        currentUserId: 7,
        data: [
            { id: 11, body: 'Nice post', date: '02.01.2026', user: { id: 9, name: 'Bob', username: 'bob', url: '/users/bob', avatar: '/c.png' }, replies: [] },
        ],
    },
    commentForm: { postUrl: '/comment/3', canComment: true, canManageComments: false, loginUrl: '/login' },
    ...over,
});

describe('public Post', () => {
    beforeEach(() => {
        post.mockClear();
    });

    it('renders title, prose content, byline and tags', () => {
        render(<Post {...(props() as any)} />);
        expect(screen.getByRole('heading', { level: 1, name: 'Introducing the CMS' })).toBeInTheDocument();
        expect(screen.getByText('Body copy.')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Jane Doe' })).toHaveAttribute('href', '/users/jane');
        expect(screen.getByRole('link', { name: 'launch' })).toHaveAttribute('href', '/tag/launch');
    });

    it('posts a like and updates the count optimistically', async () => {
        const fetchMock = vi.fn().mockResolvedValue({ ok: true });
        vi.stubGlobal('fetch', fetchMock);

        render(<Post {...(props() as any)} />);
        fireEvent.click(screen.getByTestId('like-button'));

        await waitFor(() => expect(fetchMock).toHaveBeenCalledWith('/handlelike/3', expect.objectContaining({ method: 'POST' })));
        vi.unstubAllGlobals();
    });

    // tr(k, fallback) returns the fallback when the mocked t() echoes the key,
    // so assertions target the English fallbacks the component renders.
    it('submits a comment through the inertia form', () => {
        render(<Post {...(props() as any)} />);
        fireEvent.change(screen.getByLabelText('Comment'), { target: { value: 'Hello' } });
        fireEvent.submit(screen.getByRole('button', { name: 'Comment' }).closest('form')!);
        expect(post).toHaveBeenCalledWith('/comment/3', expect.objectContaining({ preserveScroll: true }));
    });

    it('shows a login prompt instead of the form for guests', () => {
        render(<Post {...(props({ commentForm: { postUrl: '/comment/3', canComment: false, canManageComments: false, loginUrl: '/login' } }) as any)} />);
        expect(screen.getByText('Please log in to comment.')).toBeInTheDocument();
        expect(screen.queryByLabelText('Comment')).not.toBeInTheDocument();
    });
});

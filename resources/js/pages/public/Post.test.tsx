import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const post = vi.fn();
const put = vi.fn();
const del = vi.fn();

vi.mock('@inertiajs/react', () => ({
    Link: ({ children, prefetch, cacheFor, preserveScroll, ...p }: any) => <a {...p}>{children}</a>,
    router: { put: (...a: any[]) => put(...a), delete: (...a: any[]) => del(...a) },
    usePage: () => ({ props: {} }),
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
        coverSeed: 'introducing-the-cms',
        date: '01.01.2026',
        dateIso: '2026-01-01T00:00:00+00:00',
        likes: 2,
        liked: false,
        likeUrl: '/handlelike/3',
        author: { name: 'Jane Doe', username: 'jane', url: '/users/jane', avatar: '/a.png' },
        category: { title: 'News', url: '/category/news' },
        tags: [{ name: 'launch', url: '/tag/launch' }],
    },
    related: [{ title: 'Another', url: '/posts/another', excerpt: 'x', date: '2026-01-02', thumbnail: null, coverSeed: 'another' }],
    comments: {
        total: 1,
        currentPage: 1,
        lastPage: 1,
        currentUserId: 7,
        data: [
            { id: 11, body: 'Nice post', date: '02.01.2026', user: { id: 9, name: 'Bob', username: 'bob', url: '/users/bob', avatar: '/c.png' }, replies: [] },
        ],
    },
    commentForm: {
        postUrl: '/comment/3',
        editUrl: '/posts/handlecomment',
        deleteBase: '/posts/deletecomment',
        canComment: true,
        canManageComments: false,
        loginUrl: '/login',
    },
    ...over,
});

// A comment authored by the current user (id 7), so owner controls appear.
const ownComment = {
    id: 11,
    body: 'My own comment',
    date: '02.01.2026',
    user: { id: 7, name: 'Me', username: 'me', url: '/users/me', avatar: '/c.png' },
    replies: [],
};

describe('public Post', () => {
    beforeEach(() => {
        post.mockClear();
        put.mockClear();
        del.mockClear();
    });

    it('hides the preview banner by default and shows it in preview mode', () => {
        const { rerender } = render(<Post {...(props() as any)} />);
        expect(screen.queryByTestId('preview-banner')).not.toBeInTheDocument();

        rerender(<Post {...(props({ preview: true }) as any)} />);
        expect(screen.getByTestId('preview-banner')).toBeInTheDocument();
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

    it('shows edit and delete controls on a comment the user owns', () => {
        render(<Post {...(props({ comments: { total: 1, currentPage: 1, lastPage: 1, currentUserId: 7, data: [ownComment] } }) as any)} />);
        expect(screen.getByTestId('comment-edit')).toBeInTheDocument();
        expect(screen.getByTestId('comment-delete')).toBeInTheDocument();
    });

    it('hides edit and delete on comments the user does not own', () => {
        render(<Post {...(props() as any)} />); // seeded comment is user.id 9, not manageable
        expect(screen.queryByTestId('comment-edit')).not.toBeInTheDocument();
        expect(screen.queryByTestId('comment-delete')).not.toBeInTheDocument();
    });

    it('shows moderator controls when the user can manage comments', () => {
        render(
            <Post
                {...(props({
                    commentForm: { postUrl: '/comment/3', editUrl: '/posts/handlecomment', deleteBase: '/posts/deletecomment', canComment: true, canManageComments: true, loginUrl: '/login' },
                }) as any)}
            />,
        );
        expect(screen.getByTestId('comment-edit')).toBeInTheDocument();
    });

    it('saves an edit via router.put with the comment id and new text', () => {
        render(<Post {...(props({ comments: { total: 1, currentPage: 1, lastPage: 1, currentUserId: 7, data: [ownComment] } }) as any)} />);
        fireEvent.click(screen.getByTestId('comment-edit'));
        fireEvent.change(screen.getByTestId('comment-edit-input'), { target: { value: 'edited text' } });
        fireEvent.click(screen.getByTestId('comment-edit-save'));
        expect(put).toHaveBeenCalledWith('/posts/handlecomment', { updated_comment_id: 11, comment: 'edited text' }, expect.objectContaining({ preserveScroll: true }));
    });

    it('deletes via router.delete after confirmation', () => {
        vi.spyOn(window, 'confirm').mockReturnValue(true);
        render(<Post {...(props({ comments: { total: 1, currentPage: 1, lastPage: 1, currentUserId: 7, data: [ownComment] } }) as any)} />);
        fireEvent.click(screen.getByTestId('comment-delete'));
        expect(del).toHaveBeenCalledWith('/posts/deletecomment/11', expect.objectContaining({ data: { commentId: 11 }, preserveScroll: true }));
    });
});

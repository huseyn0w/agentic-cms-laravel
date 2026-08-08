import { Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { PublicLayout } from '@/layouts/PublicLayout';
import { PreviewBanner } from '@/components/PreviewBanner';
import { PostImage } from '@/components/public/PostImage';
import type { Shell } from '@/layouts/PublicLayout';

interface Author {
    name: string;
    username: string;
    url: string;
    avatar: string;
}

interface CommentUser {
    id: number;
    name: string;
    username: string;
    url: string;
    avatar: string;
}

interface CommentNode {
    id: number;
    body: string;
    date: string;
    user: CommentUser;
    replies?: CommentNode[];
}

interface PostProps {
    shell: Shell;
    preview?: boolean;
    currentUserId: number | null;
    post: {
        id: number;
        title: string;
        content: string;
        thumbnail: string | null;
        coverSeed: string;
        date: string;
        dateIso: string;
        likes: number;
        liked: boolean;
        likeUrl: string;
        author: Author;
        category: { title: string; url: string } | null;
        tags: { name: string; url: string }[];
    };
    related: { title: string; url: string; excerpt: string; date: string; thumbnail: string | null; coverSeed: string }[];
    comments: { total: number; data: CommentNode[]; currentPage: number; lastPage: number; currentUserId: number | null };
    commentForm: {
        postUrl: string;
        editUrl: string;
        deleteBase: string;
        canComment: boolean;
        canManageComments: boolean;
        loginUrl: string;
    };
}

const HeartIcon = () => (
    <svg className="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path d="M10 17.5 3.5 11a4 4 0 1 1 6.5-4.6A4 4 0 1 1 16.5 11z" />
    </svg>
);

/**
 * Public post detail. SEO head (Article + BreadcrumbList JSON-LD) is
 * server-rendered by Blade (seo-meta detects the posts route) — no <Head> here.
 * The thread renders the create form plus owner/admin inline edit + delete.
 */
export default function Post({ shell, preview = false, currentUserId, post, related, comments, commentForm }: PostProps) {
    const { t } = useTranslation();
    const tr = (k: string, f: string) => (t(k) === k ? f : t(k));

    return (
        <PublicLayout shell={shell}>
            {preview && <PreviewBanner />}
            <article className="mx-auto max-w-[720px] px-5 py-14 sm:px-8 sm:py-16">
                {post.category && (
                    <a href={post.category.url} className="transition-colors hover:text-[var(--accent)]">
                        <p className="mb-2 font-mono text-xs uppercase tracking-[0.08em] text-[var(--text-subtle)]">{post.category.title}</p>
                    </a>
                )}

                <h1 className="mt-3 font-semibold leading-[1.05] tracking-[-0.03em] text-[var(--text)]" style={{ fontSize: 'clamp(2.25rem,4.5vw,3.25rem)' }}>
                    {post.title}
                </h1>

                <div className="mt-6 flex items-center gap-3 border-b border-[var(--border)] pb-6">
                    <img src={post.author.avatar} alt={post.author.name} width={44} height={44} className="h-11 w-11 rounded-full object-cover ring-1 ring-[var(--border)]" />
                    <div className="min-w-0">
                        <a href={post.author.url} className="text-base font-medium text-[var(--text)] transition-colors hover:text-[var(--accent)]">
                            {post.author.name}
                        </a>
                        <div className="mt-0.5 font-mono text-xs text-[var(--text-muted)]">
                            <time dateTime={post.dateIso}>{post.date}</time>
                        </div>
                    </div>
                </div>

                <figure className="mt-10 overflow-hidden rounded-xl bg-[var(--surface-2)]">
                    <PostImage
                        thumbnail={post.thumbnail}
                        coverSeed={post.coverSeed}
                        title={post.title}
                        alt={post.title}
                        width={1280}
                        height={720}
                        imgClassName="aspect-[16/9] w-full object-cover"
                        coverClassName="aspect-[16/9] w-full"
                    />
                </figure>

                <div className="article-prose mt-10" dangerouslySetInnerHTML={{ __html: post.content }} />

                {post.tags.length > 0 && (
                    <div className="mt-10 flex flex-wrap items-center gap-2">
                        <span className="mr-1 font-mono text-xs uppercase tracking-[0.08em] text-[var(--text-muted)]">{tr('default/post.tags', 'Tags')}</span>
                        {post.tags.map((tag) => (
                            <a
                                key={tag.url}
                                href={tag.url}
                                className="rounded-full border border-[var(--border)] px-3 py-1 text-sm text-[var(--text-subtle)] transition-colors hover:border-[var(--accent)] hover:text-[var(--accent)]"
                            >
                                {tag.name}
                            </a>
                        ))}
                    </div>
                )}

                <LikeBar
                    postId={post.id}
                    userId={currentUserId}
                    likeUrl={post.likeUrl}
                    initialLiked={post.liked}
                    initialLikes={post.likes}
                    canLike={commentForm.canComment}
                    tr={tr}
                />

                {related.length > 0 && (
                    <section className="mt-16 border-t border-[var(--border)] pt-12">
                        <h2 className="text-2xl font-semibold tracking-[-0.02em] text-[var(--text)]">{tr('default/post.related_posts', 'Related posts')}</h2>
                        <div className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {related.map((r) => (
                                <Link key={r.url} href={r.url} prefetch="hover" cacheFor="30s" className="group block overflow-hidden rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] transition duration-200 hover:-translate-y-0.5 hover:border-[var(--accent)]">
                                    <div className="overflow-hidden bg-[var(--surface-2)]">
                                        <PostImage
                                            thumbnail={r.thumbnail}
                                            coverSeed={r.coverSeed}
                                            title={r.title}
                                            imgClassName="aspect-[16/9] w-full object-cover transition duration-300 group-hover:scale-105"
                                            coverClassName="aspect-[16/9] w-full"
                                        />
                                    </div>
                                    <div className="p-4">
                                    <p className="font-mono text-xs text-[var(--text-faint)]">{r.date}</p>
                                    <h3 className="mt-1 text-lg font-semibold tracking-[-0.01em] text-[var(--text)] transition-colors group-hover:text-[var(--accent)]">{r.title}</h3>
                                    {r.excerpt && <p className="mt-1 line-clamp-2 text-sm text-[var(--text-subtle)]">{r.excerpt}</p>}
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </section>
                )}

                <CommentThread comments={comments} commentForm={commentForm} postId={post.id} tr={tr} />
            </article>
        </PublicLayout>
    );
}

function LikeBar({
    postId,
    userId,
    likeUrl,
    initialLiked,
    initialLikes,
    canLike,
    tr,
}: {
    postId: number;
    userId: number | null;
    likeUrl: string;
    initialLiked: boolean;
    initialLikes: number;
    canLike: boolean;
    tr: (k: string, f: string) => string;
}) {
    const [liked, setLiked] = useState(initialLiked);
    const [likes, setLikes] = useState(initialLikes);
    const [busy, setBusy] = useState(false);

    const toggle = async () => {
        if (busy || userId === null) return;
        setBusy(true);
        const nextLiked = !liked;
        // Optimistic update; revert on failure.
        setLiked(nextLiked);
        setLikes((n) => n + (nextLiked ? 1 : -1));
        try {
            const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
            const res = await fetch(likeUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: JSON.stringify({ postId, userId }),
            });
            if (!res.ok) throw new Error('like failed');
        } catch {
            setLiked(!nextLiked);
            setLikes((n) => n + (nextLiked ? -1 : 1));
        } finally {
            setBusy(false);
        }
    };

    const summary =
        likes > 0
            ? `${likes} ${tr('default/post.multiple_like_after', 'like this')}`
            : tr('default/post.nobody_likes', 'No likes yet');

    return (
        <div className="mt-12 flex flex-wrap items-center justify-between gap-4 rounded-lg border border-[var(--border)] bg-[var(--surface)] px-6 py-5">
            {canLike ? (
                <div className="flex flex-wrap items-center gap-4">
                    <button
                        type="button"
                        onClick={toggle}
                        disabled={busy}
                        data-testid="like-button"
                        className={
                            'inline-flex items-center gap-2 rounded-full border px-5 py-2.5 text-sm font-medium transition active:scale-[0.98] disabled:opacity-60 ' +
                            (liked ? 'border-[var(--primary)] bg-[var(--primary)] text-[var(--primary-contrast)]' : 'border-[var(--border)] text-[var(--text)] hover:border-[var(--border-strong)]')
                        }
                    >
                        <HeartIcon />
                        <span>{liked ? tr('default/post.dislike', 'Unlike') : tr('default/post.like', 'Like')}</span>
                    </button>
                    <p className="text-sm text-[var(--text-muted)]">{summary}</p>
                </div>
            ) : (
                <div className="flex items-center gap-2.5 text-sm text-[var(--text-muted)]">
                    <span className="text-[var(--primary)]">
                        <HeartIcon />
                    </span>
                    <span>{summary}</span>
                </div>
            )}
        </div>
    );
}

function CommentCard({
    comment,
    isReply,
    canManage,
    editUrl,
    deleteBase,
    tr,
}: {
    comment: CommentNode;
    isReply?: boolean;
    canManage: boolean;
    editUrl: string;
    deleteBase: string;
    tr: (k: string, f: string) => string;
}) {
    const [editing, setEditing] = useState(false);
    const [text, setText] = useState(comment.body);
    const [saving, setSaving] = useState(false);

    const save = () => {
        setSaving(true);
        router.put(
            editUrl,
            { updated_comment_id: comment.id, comment: text },
            { preserveScroll: true, onSuccess: () => setEditing(false), onFinish: () => setSaving(false) },
        );
    };

    const remove = () => {
        if (!window.confirm(tr('default/post.delete_confirm', 'Delete this comment?'))) return;
        router.delete(`${deleteBase}/${comment.id}`, { data: { commentId: comment.id }, preserveScroll: true });
    };

    return (
        <article className="flex gap-4" data-testid={isReply ? 'comment-reply' : 'comment-card'}>
            <img src={comment.user.avatar} alt={comment.user.name} width={40} height={40} className="h-10 w-10 shrink-0 rounded-full object-cover ring-1 ring-[var(--border)]" />
            <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                    <a href={comment.user.url} className="text-base font-medium text-[var(--text)] transition-colors hover:text-[var(--accent)]">
                        {comment.user.name}
                    </a>
                    <span className="text-xs text-[var(--text-subtle)]">{comment.date}</span>
                </div>

                {editing ? (
                    <div className="mt-2">
                        <textarea
                            value={text}
                            onChange={(e) => setText(e.target.value)}
                            rows={3}
                            data-testid="comment-edit-input"
                            className="w-full rounded-sm border border-[var(--border-strong)] bg-[var(--surface)] px-3 py-2 text-base text-[var(--text)] focus:border-[var(--ring)] focus:outline-none focus:ring-2 focus:ring-[var(--ring)]/30"
                        />
                        <div className="mt-2 flex gap-2">
                            <button
                                type="button"
                                onClick={save}
                                disabled={saving}
                                data-testid="comment-edit-save"
                                className="rounded-md bg-[var(--primary)] px-3 py-1.5 text-xs font-medium text-[var(--primary-contrast)] transition hover:opacity-90 disabled:opacity-60"
                            >
                                {tr('default/post.save', 'Save')}
                            </button>
                            <button
                                type="button"
                                onClick={() => {
                                    setEditing(false);
                                    setText(comment.body);
                                }}
                                className="rounded-md px-3 py-1.5 text-xs font-medium text-[var(--text-muted)] transition hover:text-[var(--text)]"
                            >
                                {tr('default/post.cancel', 'Cancel')}
                            </button>
                        </div>
                    </div>
                ) : (
                    <p className="mt-1.5 text-base leading-relaxed text-[var(--text-muted)]">{comment.body}</p>
                )}

                {canManage && !editing && (
                    <div className="mt-2 flex gap-3 text-xs">
                        <button type="button" onClick={() => setEditing(true)} data-testid="comment-edit" className="font-medium text-[var(--text-muted)] transition-colors hover:text-[var(--primary)]">
                            {tr('default/post.edit', 'Edit')}
                        </button>
                        <button type="button" onClick={remove} data-testid="comment-delete" className="font-medium text-[var(--text-muted)] transition-colors hover:text-[var(--error)]">
                            {tr('default/post.delete', 'Delete')}
                        </button>
                    </div>
                )}
            </div>
        </article>
    );
}

function CommentThread({
    comments,
    commentForm,
    postId,
    tr,
}: {
    comments: PostProps['comments'];
    commentForm: PostProps['commentForm'];
    postId: number;
    tr: (k: string, f: string) => string;
}) {
    const form = useForm<{ comment: string; parent_id: string; post_id: number }>({ comment: '', parent_id: '', post_id: postId });
    const [submitted, setSubmitted] = useState(false);
    const [replyingTo, setReplyingTo] = useState<string | null>(null);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(commentForm.postUrl, {
            preserveScroll: true,
            onSuccess: () => {
                setSubmitted(true);
                setReplyingTo(null);
                form.reset('comment', 'parent_id');
            },
        });
    };

    const startReply = (id: number, name: string) => {
        form.setData('parent_id', String(id));
        setReplyingTo(name);
        document.getElementById('comment-area')?.scrollIntoView({ behavior: 'smooth' });
    };

    const canManage = (c: CommentNode) => comments.currentUserId === c.user.id || commentForm.canManageComments;

    return (
        <>
            <section className="mt-16">
                <h2 className="text-2xl font-semibold tracking-[-0.02em] text-[var(--text)]">
                    {comments.total} {tr('default/post.comments', 'Comments')}
                </h2>

                {comments.data.length > 0 ? (
                    <div className="mt-8 space-y-8">
                        {comments.data.map((comment) => (
                            <div key={comment.id}>
                                <div className="flex items-start justify-between gap-4">
                                    <div className="flex-1">
                                        <CommentCard comment={comment} canManage={canManage(comment)} editUrl={commentForm.editUrl} deleteBase={commentForm.deleteBase} tr={tr} />
                                    </div>
                                    {commentForm.canComment && comments.currentUserId !== comment.user.id && (
                                        <button
                                            type="button"
                                            onClick={() => startReply(comment.id, comment.user.name)}
                                            className="shrink-0 text-sm font-medium text-[var(--text-muted)] transition-colors hover:text-[var(--primary)]"
                                        >
                                            {tr('default/post.reply', 'Reply')}
                                        </button>
                                    )}
                                </div>
                                {comment.replies && comment.replies.length > 0 && (
                                    <div data-comment-replies className="ml-6 mt-8 space-y-8 border-l border-[var(--border)] pl-6 sm:ml-10 sm:pl-8">
                                        {comment.replies.map((child) => (
                                            <CommentCard key={child.id} comment={child} isReply canManage={canManage(child)} editUrl={commentForm.editUrl} deleteBase={commentForm.deleteBase} tr={tr} />
                                        ))}
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                ) : (
                    <p className="mt-6 text-[var(--text-muted)]">{tr('default/post.no_comments', 'No comments yet')}</p>
                )}

                {comments.lastPage > 1 && (
                    <nav className="mt-10 flex items-center gap-2" aria-label="Comments pagination">
                        {Array.from({ length: comments.lastPage }, (_, i) => i + 1).map((page) => (
                            <Link
                                key={page}
                                href={`?page=${page}`}
                                preserveScroll
                                className={
                                    'rounded-md px-3 py-1.5 text-sm transition-colors ' +
                                    (page === comments.currentPage ? 'bg-[var(--primary)] text-[var(--primary-contrast)]' : 'text-[var(--text-muted)] hover:bg-[var(--surface-2)] hover:text-[var(--text)]')
                                }
                            >
                                {page}
                            </Link>
                        ))}
                    </nav>
                )}
            </section>

            <section className="mt-16 scroll-mt-24" id="comment-area">
                {commentForm.canComment ? (
                    <>
                        <h2 className="text-2xl font-semibold tracking-[-0.02em] text-[var(--text)]">{tr('default/post.leave_reply', 'Leave a reply')}</h2>

                        {submitted && (
                            <div className="mt-6 rounded-md border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3 text-sm text-[var(--text)]" role="status">
                                {commentForm.canManageComments
                                    ? tr('default/post.comment_added', 'Your comment was added.')
                                    : tr('default/post.comment_send_to_approve', 'Your comment was sent for approval.')}
                            </div>
                        )}

                        {replyingTo && (
                            <p className="mt-4 text-sm text-[var(--text-muted)]">
                                {tr('default/post.reply', 'Reply')} → {replyingTo}
                            </p>
                        )}

                        <form onSubmit={submit} className="mt-8">
                            <label htmlFor="comment" className="mb-1.5 block text-sm font-medium text-[var(--text)]">
                                {tr('default/post.comment', 'Comment')}
                            </label>
                            <textarea
                                id="comment"
                                name="comment"
                                rows={5}
                                required
                                value={form.data.comment}
                                onChange={(e) => form.setData('comment', e.target.value)}
                                placeholder={tr('default/post.comment', 'Comment')}
                                className="w-full resize-y rounded-sm border border-[var(--border-strong)] bg-[var(--surface)] px-3 py-2.5 text-base text-[var(--text)] placeholder:text-[var(--text-subtle)] focus:border-[var(--ring)] focus:outline-none focus:ring-2 focus:ring-[var(--ring)]/30"
                                aria-invalid={form.errors.comment ? 'true' : undefined}
                            />
                            {form.errors.comment && <p className="mt-1 text-sm text-[var(--error)]">{form.errors.comment}</p>}
                            <div className="mt-4">
                                <button
                                    type="submit"
                                    disabled={form.processing}
                                    className="rounded-md bg-[var(--primary)] px-5 py-2.5 text-sm font-medium text-[var(--primary-contrast)] transition hover:opacity-90 disabled:opacity-60"
                                >
                                    {tr('default/post.comment', 'Comment')}
                                </button>
                            </div>
                        </form>
                    </>
                ) : (
                    <div className="rounded-lg border border-[var(--border)] bg-[var(--surface)] px-6 py-8 text-center">
                        <p className="text-[var(--text-muted)]">{tr('default/post.comment_auth', 'Please log in to comment.')}</p>
                        <div className="mt-4">
                            <a href={commentForm.loginUrl} className="inline-flex rounded-md border border-[var(--border)] px-4 py-2 text-sm text-[var(--text)] hover:bg-[var(--surface-2)]">
                                {tr('default/header.login', 'Log in')}
                            </a>
                        </div>
                    </div>
                )}
            </section>
        </>
    );
}

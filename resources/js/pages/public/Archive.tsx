import { Link } from '@inertiajs/react';
import { PublicLayout } from '@/layouts/PublicLayout';
import type { Shell } from '@/layouts/PublicLayout';

interface ArchivePost {
    title: string;
    url: string;
    excerpt: string;
    image: string;
    date: string;
}

interface ArchiveProps {
    shell: Shell;
    archive: {
        title: string;
        crumbs: { label: string; url: string | null }[];
        posts: ArchivePost[];
        currentPage: number;
        lastPage: number;
        total: number;
        pageBaseUrl: string;
        emptyText: string;
    };
}

/**
 * Shared post-listing archive for category and tag pages. SEO head is
 * server-rendered by Blade (seo-meta emits CollectionPage + BreadcrumbList for
 * the category routes) — no <Head> here.
 */
export default function Archive({ shell, archive }: ArchiveProps) {
    return (
        <PublicLayout shell={shell}>
            <header className="border-b border-[var(--border)] bg-[var(--surface-2)]">
                <div className="mx-auto max-w-[1080px] px-5 py-12 sm:px-8 sm:py-16">
                    <nav aria-label="Breadcrumb" className="mb-3 flex flex-wrap items-center gap-1.5 font-mono text-xs text-[var(--text-muted)]">
                        {archive.crumbs.map((crumb, i) => (
                            <span key={crumb.label} className="flex items-center gap-1.5">
                                {i > 0 && <span aria-hidden="true">/</span>}
                                {crumb.url ? (
                                    <a href={crumb.url} className="transition-colors hover:text-[var(--text)]">
                                        {crumb.label}
                                    </a>
                                ) : (
                                    <span className="text-[var(--text)]">{crumb.label}</span>
                                )}
                            </span>
                        ))}
                    </nav>
                    <h1 className="text-[clamp(1.875rem,3vw,2.441rem)] font-medium leading-[1.15] tracking-[-0.01em] text-[var(--text)]">
                        {archive.title}
                    </h1>
                </div>
            </header>

            <section className="mx-auto max-w-[1080px] px-5 py-16 sm:px-8 sm:py-20">
                {archive.posts.length > 0 ? (
                    <>
                        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {archive.posts.map((post) => (
                                <Link
                                    key={post.url}
                                    href={post.url}
                                    prefetch="hover"
                                    cacheFor="30s"
                                    data-testid="archive-post"
                                    className="group flex flex-col overflow-hidden rounded-lg border border-[var(--border)] bg-[var(--surface)] transition hover:border-[var(--border-strong)] hover:shadow-card"
                                >
                                    <div className="aspect-[16/9] overflow-hidden bg-[var(--surface-2)]">
                                        <img src={post.image} alt="" className="h-full w-full object-cover transition duration-300 group-hover:scale-105" />
                                    </div>
                                    <div className="flex flex-1 flex-col p-5">
                                        <p className="font-mono text-xs uppercase tracking-[0.06em] text-[var(--text-muted)]">{post.date}</p>
                                        <h2 className="mt-2 text-lg font-medium text-[var(--text)]">{post.title}</h2>
                                        {post.excerpt && <p className="mt-2 line-clamp-3 text-sm text-[var(--text-muted)]">{post.excerpt}</p>}
                                    </div>
                                </Link>
                            ))}
                        </div>

                        {archive.lastPage > 1 && (
                            <nav className="mt-12 flex flex-wrap items-center gap-2" aria-label="Pagination">
                                {Array.from({ length: archive.lastPage }, (_, i) => i + 1).map((page) => (
                                    <Link
                                        key={page}
                                        href={`${archive.pageBaseUrl}/page/${page}`}
                                        className={
                                            'rounded-md px-3.5 py-2 text-sm transition-colors ' +
                                            (page === archive.currentPage
                                                ? 'bg-[var(--primary)] text-[var(--primary-contrast)]'
                                                : 'text-[var(--text-muted)] hover:bg-[var(--surface-2)] hover:text-[var(--text)]')
                                        }
                                        aria-current={page === archive.currentPage ? 'page' : undefined}
                                    >
                                        {page}
                                    </Link>
                                ))}
                            </nav>
                        )}
                    </>
                ) : (
                    <div className="rounded-lg border border-dashed border-[var(--border)] px-6 py-16 text-center" data-testid="archive-empty">
                        <p className="font-sans text-[var(--text-muted)]">{archive.emptyText}</p>
                    </div>
                )}
            </section>
        </PublicLayout>
    );
}

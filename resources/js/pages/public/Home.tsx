import { Link } from '@inertiajs/react';
import { PublicLayout } from '@/layouts/PublicLayout';
import { PreviewBanner } from '@/components/PreviewBanner';
import type { Shell } from '@/layouts/PublicLayout';

interface PostCard {
    title: string;
    url: string;
    image: string;
    excerpt: string;
    date: string;
}

interface Author {
    name: string;
    image: string;
    position: string | null;
    linkedinUrl: string | null;
    linkedinBlank: boolean;
}

interface HomeProps {
    shell: Shell;
    preview?: boolean;
    page: { title: string };
    hero: { headline: string | null; background: string | null };
    postsSection: { headline: string | null; description: string | null; posts: PostCard[] };
    about: { headline: string | null; description: string | null; body: string | null; authors: Author[] };
}

/**
 * Public homepage. The <head> (title, meta, JSON-LD) is server-rendered by
 * Blade (partials/seo-meta.blade.php via the app-public root) — this component
 * deliberately renders no <Head>, so exactly one <title> reaches the page.
 */
export default function Home({ shell, preview = false, page, hero, postsSection, about }: HomeProps) {
    return (
        <PublicLayout shell={shell}>
            {preview && <PreviewBanner />}
            {/* Hero */}
            <section className="relative overflow-hidden" id="home">
                {hero.background ? (
                    <div className="absolute inset-0 -z-10 bg-[var(--text)]">
                        <img
                            src={hero.background}
                            alt=""
                            width={1920}
                            height={1080}
                            className="h-full w-full object-cover opacity-55"
                        />
                        <div className="absolute inset-0 bg-gradient-to-b from-black/50 via-black/30 to-black/75" />
                    </div>
                ) : (
                    <div className="absolute inset-0 -z-10 bg-[var(--text)]" />
                )}

                <div className="mx-auto flex min-h-[72vh] max-w-[1280px] items-center px-4 py-28 sm:px-6 sm:py-32 lg:px-8">
                    <div className="max-w-3xl">
                        <p className="mb-6 inline-flex items-center gap-3 font-mono text-xs uppercase tracking-[0.08em] text-white/60">
                            <span className="h-px w-8 bg-[var(--accent)]" aria-hidden="true" />
                            {page.title}
                        </p>
                        {hero.headline && (
                            <h1
                                className="font-serif text-5xl font-medium leading-[1.05] tracking-[-0.01em] text-white [text-wrap:balance] sm:text-6xl lg:text-[clamp(2.5rem,5vw,3.815rem)]"
                                dangerouslySetInnerHTML={{ __html: hero.headline }}
                            />
                        )}
                    </div>
                </div>
            </section>

            {/* Posts from category */}
            {postsSection.posts.length > 0 && (
                <section className="mx-auto max-w-[1080px] px-4 py-24 sm:px-6 sm:py-[96px] lg:px-8" id="travel">
                    <div className="mb-12 max-w-2xl">
                        {postsSection.headline && (
                            <h2 className="font-serif text-[clamp(1.875rem,3vw,2.441rem)] font-medium leading-[1.15] tracking-[-0.01em] text-[var(--text)]">
                                {postsSection.headline}
                            </h2>
                        )}
                        {postsSection.description && (
                            <p className="mt-4 text-lg leading-relaxed text-[var(--text-muted)]">{postsSection.description}</p>
                        )}
                    </div>

                    <div className="grid gap-8 sm:grid-cols-2">
                        {postsSection.posts.map((post) => (
                            <Link
                                key={post.url}
                                href={post.url}
                                prefetch="hover"
                                cacheFor="30s"
                                data-testid="post-link"
                                className="group block overflow-hidden rounded-lg border border-[var(--border)] bg-[var(--surface)] transition hover:border-[var(--border-strong)] hover:shadow-card"
                            >
                                <div className="aspect-[16/9] overflow-hidden bg-[var(--surface-2)]">
                                    <img
                                        src={post.image}
                                        alt=""
                                        className="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                    />
                                </div>
                                <div className="p-5">
                                    <p className="font-mono text-xs uppercase tracking-[0.06em] text-[var(--text-muted)]">{post.date}</p>
                                    <h3 className="mt-2 font-serif text-xl font-medium text-[var(--text)]">{post.title}</h3>
                                    {post.excerpt && <p className="mt-2 line-clamp-2 text-sm text-[var(--text-muted)]">{post.excerpt}</p>}
                                </div>
                            </Link>
                        ))}
                    </div>
                </section>
            )}

            {/* About / team */}
            <section className="border-t border-[var(--border)] bg-[var(--surface-2)]" id="team">
                <div className="mx-auto max-w-[1080px] px-4 py-24 sm:px-6 sm:py-[96px] lg:px-8">
                    <div className="mb-14 max-w-2xl">
                        {about.headline && (
                            <h2 className="font-serif text-[clamp(1.875rem,3vw,2.441rem)] font-medium leading-[1.15] tracking-[-0.01em] text-[var(--text)]">
                                {about.headline}
                            </h2>
                        )}
                        {about.description && (
                            <p className="mt-4 text-lg leading-relaxed text-[var(--text-muted)]">{about.description}</p>
                        )}
                    </div>

                    <div className="grid gap-12 lg:grid-cols-[1.1fr_1fr] lg:items-start lg:gap-16">
                        {about.body && <div className="article-prose max-w-prose" dangerouslySetInnerHTML={{ __html: about.body }} />}

                        {about.authors.length > 0 && (
                            <div className="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-2">
                                {about.authors.map((author) => (
                                    <div key={author.name} className="group rounded-lg border border-[var(--border)] bg-[var(--surface)] p-4 text-center">
                                        <div className="relative mx-auto mb-4 aspect-square w-full max-w-[140px] overflow-hidden rounded-lg bg-[var(--surface-2)]">
                                            <img
                                                src={author.image}
                                                alt={author.name}
                                                width={280}
                                                height={280}
                                                className="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                            />
                                        </div>
                                        <p className="font-serif text-base font-medium text-[var(--text)]">{author.name}</p>
                                        {author.position && <p className="mt-0.5 font-mono text-xs text-[var(--text-muted)]">{author.position}</p>}
                                        {author.linkedinUrl && (
                                            <a
                                                href={author.linkedinUrl}
                                                target={author.linkedinBlank ? '_blank' : '_self'}
                                                rel="noopener noreferrer"
                                                aria-label={`${author.name} on LinkedIn`}
                                                className="mt-2 inline-flex text-[var(--text-muted)] transition hover:text-[var(--primary)]"
                                            >
                                                <svg className="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                    <path d="M4.98 3.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM3 9h4v12H3zM10 9h3.8v1.7h.05c.53-1 1.83-2.05 3.77-2.05C21.4 8.65 22 11 22 14.1V21h-4v-6.1c0-1.45-.03-3.3-2-3.3s-2.3 1.57-2.3 3.2V21h-4z" />
                                                </svg>
                                            </a>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

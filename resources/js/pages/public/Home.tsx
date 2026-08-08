import { Link } from '@inertiajs/react';
import { PublicLayout } from '@/layouts/PublicLayout';
import { PreviewBanner } from '@/components/PreviewBanner';
import { PostImage } from '@/components/public/PostImage';
import type { Shell } from '@/layouts/PublicLayout';

interface PostCard {
    title: string;
    url: string;
    thumbnail: string | null;
    coverSeed: string;
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
    const hasImage = Boolean(hero.background);

    return (
        <PublicLayout shell={shell}>
            {preview && <PreviewBanner />}

            {/* Hero — a light gradient-lit band, or a dark image band when the
                page sets a background. */}
            <section className="relative overflow-hidden border-b border-[var(--border)]" id="home">
                {hasImage ? (
                    <div className="absolute inset-0 -z-10">
                        <img src={hero.background as string} alt="" width={1920} height={1080} className="h-full w-full object-cover" />
                        <div className="absolute inset-0 bg-gradient-to-b from-[rgba(10,10,10,0.55)] via-[rgba(10,10,10,0.4)] to-[rgba(10,10,10,0.8)]" />
                    </div>
                ) : (
                    <div className="hero-glow absolute inset-0 -z-10" aria-hidden="true" />
                )}

                <div className="mx-auto max-w-[76rem] px-5 py-28 sm:px-8 sm:py-36 lg:py-40">
                    <div className="max-w-3xl">
                        <p
                            className={
                                'mb-6 inline-flex items-center gap-3 font-mono text-xs uppercase tracking-[0.1em] ' +
                                (hasImage ? 'text-white/70' : 'text-[var(--text-subtle)]')
                            }
                        >
                            <span className="h-px w-8" style={{ backgroundImage: 'var(--grad)' }} aria-hidden="true" />
                            {page.title}
                        </p>
                        {hero.headline && (
                            <h1
                                className={
                                    'text-[clamp(2.5rem,6vw,4rem)] font-semibold leading-[1.03] tracking-[-0.03em] [text-wrap:balance] ' +
                                    (hasImage ? 'text-white' : 'text-[var(--text)]')
                                }
                                dangerouslySetInnerHTML={{ __html: hero.headline }}
                            />
                        )}
                    </div>
                </div>
            </section>

            {/* Posts from category */}
            {postsSection.posts.length > 0 && (
                <section className="mx-auto max-w-[76rem] px-5 py-24 sm:px-8" id="posts">
                    <div className="mb-12 max-w-2xl">
                        {postsSection.headline && (
                            <h2 className="text-[clamp(1.75rem,3.5vw,2.5rem)] font-semibold leading-[1.1] tracking-[-0.025em] text-[var(--text)]">
                                {postsSection.headline}
                            </h2>
                        )}
                        {postsSection.description && (
                            <p className="mt-4 text-[17px] leading-relaxed text-[var(--text-subtle)]">{postsSection.description}</p>
                        )}
                    </div>

                    <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {postsSection.posts.map((post) => (
                            <Link
                                key={post.url}
                                href={post.url}
                                prefetch="hover"
                                cacheFor="30s"
                                data-testid="post-link"
                                className="group flex flex-col overflow-hidden rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] transition duration-200 hover:-translate-y-0.5 hover:border-[var(--accent)] hover:shadow-[0_8px_30px_rgba(9,9,11,0.08)]"
                            >
                                <div className="aspect-[16/10] overflow-hidden bg-[var(--surface-2)]">
                                    <PostImage
                                        thumbnail={post.thumbnail}
                                        coverSeed={post.coverSeed}
                                        title={post.title}
                                        imgClassName="h-full w-full object-cover transition duration-300 group-hover:scale-[1.04]"
                                        coverClassName="h-full w-full transition duration-300 group-hover:scale-[1.04]"
                                    />
                                </div>
                                <div className="flex flex-1 flex-col p-5">
                                    <p className="font-mono text-[11px] uppercase tracking-[0.08em] text-[var(--text-faint)]">{post.date}</p>
                                    <h3 className="mt-2 text-[19px] font-semibold leading-snug tracking-[-0.015em] text-[var(--text)] transition-colors group-hover:text-[var(--accent)]">
                                        {post.title}
                                    </h3>
                                    {post.excerpt && <p className="mt-2 line-clamp-2 text-sm leading-relaxed text-[var(--text-subtle)]">{post.excerpt}</p>}
                                </div>
                            </Link>
                        ))}
                    </div>
                </section>
            )}

            {/* About / team */}
            <section className="border-t border-[var(--border)] bg-[var(--surface-2)]" id="about">
                <div className="mx-auto max-w-[76rem] px-5 py-24 sm:px-8">
                    <div className="mb-14 max-w-2xl">
                        {about.headline && (
                            <h2 className="text-[clamp(1.75rem,3.5vw,2.5rem)] font-semibold leading-[1.1] tracking-[-0.025em] text-[var(--text)]">
                                {about.headline}
                            </h2>
                        )}
                        {about.description && (
                            <p className="mt-4 text-[17px] leading-relaxed text-[var(--text-subtle)]">{about.description}</p>
                        )}
                    </div>

                    <div className="grid gap-12 lg:grid-cols-[1.1fr_1fr] lg:items-start lg:gap-16">
                        {about.body && <div className="article-prose max-w-prose" dangerouslySetInnerHTML={{ __html: about.body }} />}

                        {about.authors.length > 0 && (
                            <div className="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-2">
                                {about.authors.map((author) => (
                                    <div
                                        key={author.name}
                                        className="group rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-4 text-center transition-colors hover:border-[var(--border-strong)]"
                                    >
                                        <div className="relative mx-auto mb-4 aspect-square w-full max-w-[140px] overflow-hidden rounded-[var(--radius-md)] bg-[var(--surface-2)]">
                                            <img
                                                src={author.image}
                                                alt={author.name}
                                                width={280}
                                                height={280}
                                                className="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                            />
                                        </div>
                                        <p className="text-[15px] font-semibold tracking-[-0.01em] text-[var(--text)]">{author.name}</p>
                                        {author.position && <p className="mt-0.5 font-mono text-[11px] text-[var(--text-subtle)]">{author.position}</p>}
                                        {author.linkedinUrl && (
                                            <a
                                                href={author.linkedinUrl}
                                                target={author.linkedinBlank ? '_blank' : '_self'}
                                                rel="noopener noreferrer"
                                                aria-label={`${author.name} on LinkedIn`}
                                                className="mt-2 inline-flex text-[var(--text-subtle)] transition hover:text-[var(--accent)]"
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

import { PublicLayout } from '@/layouts/PublicLayout';
import type { Shell } from '@/layouts/PublicLayout';

interface PageProps {
    shell: Shell;
    page: { title: string; lead: string | null; content: string | null };
    crumbs: { label: string; url: string | null }[];
}

/**
 * A standard content page (About, etc.). SEO head is server-rendered by Blade
 * (seo-meta via the page model on the app-public root) — no <Head> here.
 */
export default function Page({ shell, page, crumbs }: PageProps) {
    return (
        <PublicLayout shell={shell}>
            <header className="border-b border-[var(--border)] bg-[var(--surface-2)]">
                <div className="mx-auto max-w-[1080px] px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
                    <nav aria-label="Breadcrumb" className="mb-5 flex flex-wrap items-center gap-1.5 font-mono text-xs text-[var(--text-muted)]">
                        {crumbs.map((crumb, i) => (
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
                    <h1 className="font-serif text-[clamp(2.25rem,4vw,3.052rem)] font-medium leading-[1.08] tracking-[-0.01em] text-[var(--text)] [text-wrap:balance]">
                        {page.title}
                    </h1>
                    {page.lead && <p className="mt-5 max-w-2xl text-lg leading-relaxed text-[var(--text-muted)]">{page.lead}</p>}
                </div>
            </header>

            <article className="mx-auto max-w-[720px] px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
                {page.content ? <div className="article-prose" dangerouslySetInnerHTML={{ __html: page.content }} /> : null}
            </article>
        </PublicLayout>
    );
}

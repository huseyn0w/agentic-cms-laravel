import { useTranslation } from 'react-i18next';
import { PublicLayout } from '@/layouts/PublicLayout';
import { PostImage } from '@/components/public/PostImage';
import type { Shell } from '@/layouts/PublicLayout';

interface ServiceShowProps {
    shell: Shell;
    indexUrl: string;
    service: {
        title: string;
        excerpt: string | null;
        content: string;
        thumbnail: string | null;
    };
    crumbs: { label: string; url: string | null }[];
}

/**
 * Public service detail. SEO head is server-rendered by Blade (seo-meta via the
 * service model on the app-public root) — no <Head> here.
 */
export default function ServiceShow({ shell, indexUrl, service, crumbs }: ServiceShowProps) {
    const { t } = useTranslation();
    const tr = (k: string, f: string) => (t(k) === k ? f : t(k));

    return (
        <PublicLayout shell={shell}>
            <header className="border-b border-[var(--border)] bg-[var(--surface-2)]">
                <div className="mx-auto max-w-[720px] px-5 py-10 sm:px-8 sm:py-14">
                    <nav aria-label="Breadcrumb" className="mb-3 flex flex-wrap items-center gap-1.5 font-mono text-xs text-[var(--text-muted)]">
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
                    <h1 className="leading-[1.08] tracking-[-0.01em] text-[var(--text)]" style={{ fontSize: 'clamp(2.25rem,4vw,3.052rem)' }}>
                        {service.title}
                    </h1>
                </div>
            </header>

            <article className="mx-auto max-w-[720px] px-5 py-14 sm:px-8 sm:py-16">
                {service.thumbnail && (
                    <figure className="mb-10 overflow-hidden rounded-xl bg-[var(--surface-2)]">
                        <PostImage
                            thumbnail={service.thumbnail}
                            coverSeed={service.title}
                            title={service.title}
                            alt={service.title}
                            width={1280}
                            height={720}
                            imgClassName="aspect-[16/9] w-full object-cover"
                            coverClassName="aspect-[16/9] w-full"
                        />
                    </figure>
                )}

                {service.excerpt && <p className="mb-8 text-lg leading-relaxed text-[var(--text-muted)]">{service.excerpt}</p>}

                <div className="article-prose" dangerouslySetInnerHTML={{ __html: service.content }} />

                <div className="mt-12 border-t border-[var(--border)] pt-8">
                    <a href={indexUrl} className="inline-flex items-center gap-2 text-sm font-medium text-[var(--text-muted)] transition-colors hover:text-[var(--primary)]">
                        ← {tr('services.back_to_services', 'Back to services')}
                    </a>
                </div>
            </article>
        </PublicLayout>
    );
}

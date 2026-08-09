import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { PublicLayout } from '@/layouts/PublicLayout';
import type { Shell } from '@/layouts/PublicLayout';

interface FieldMeta {
    name: string;
    label: string;
    type: string;
    options: Record<string, string>;
}

type Item = Record<string, unknown> & { id: number };

interface ContentIndexProps {
    shell: Shell;
    heading: string;
    slug: string;
    fields: FieldMeta[];
    items: Item[];
    hasDetail: boolean;
    detailBase: string;
    emptyText: string;
}

/**
 * Generic public index for a plugin content type (Projects, Experience, …).
 * Renders a card grid from the type's schema: the first text field is the title,
 * an image field is the cover, a textarea is the excerpt, other text fields are
 * meta. Each card links to the detail page, or to its URL field when the type
 * has no detail. SEO head is server-rendered by Blade — no <Head> here.
 */
export default function ContentIndex({ shell, heading, fields, items, hasDetail, detailBase, emptyText }: ContentIndexProps) {
    const { t } = useTranslation();
    const tr = (k: string, f: string) => (t(k) === k ? f : t(k));

    const titleField = fields.find((f) => f.type === 'text');
    const imageField = fields.find((f) => f.type === 'image');
    const textareaField = fields.find((f) => f.type === 'textarea');
    const urlField = fields.find((f) => f.type === 'url');
    const metaFields = fields.filter((f) => f.type === 'text' && f.name !== titleField?.name);

    const str = (item: Item, name?: string): string | null => {
        if (!name) return null;
        const value = item[name];
        return typeof value === 'string' && value !== '' ? value : null;
    };

    return (
        <PublicLayout shell={shell}>
            <header className="border-b border-[var(--border)] bg-[var(--surface-2)]">
                <div className="mx-auto max-w-7xl px-5 py-12 sm:px-8 sm:py-16">
                    <h1 className="text-[clamp(1.875rem,3vw,2.441rem)] font-medium leading-[1.15] tracking-[-0.01em] text-[var(--text)]">
                        {heading}
                    </h1>
                </div>
            </header>

            <section className="mx-auto max-w-7xl px-5 py-14 sm:px-8 sm:py-16">
                {items.length === 0 ? (
                    <div className="rounded-lg border border-dashed border-[var(--border)] px-6 py-16 text-center" data-testid="content-empty">
                        <p className="text-[var(--text-muted)]">{emptyText}</p>
                    </div>
                ) : (
                    <div className="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        {items.map((item) => {
                            const title = str(item, titleField?.name) ?? `#${item.id}`;
                            const image = str(item, imageField?.name);
                            const excerpt = str(item, textareaField?.name);
                            const external = str(item, urlField?.name);
                            const detailUrl = `${detailBase}/${item.id}`;
                            const metas = metaFields.map((f) => str(item, f.name)).filter((v): v is string => v !== null);

                            return (
                                <article key={item.id} className="flex flex-col overflow-hidden rounded-lg border border-[var(--border)] bg-[var(--surface)] transition hover:border-[var(--border-strong)] hover:shadow-card" data-testid="content-card">
                                    {image && (
                                        <img src={image} alt={title} loading="lazy" decoding="async" className="aspect-[16/10] w-full object-cover" />
                                    )}
                                    <div className="flex flex-1 flex-col p-6">
                                        {metas.length > 0 && (
                                            <div className="mb-2 text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">
                                                {metas.join(' · ')}
                                            </div>
                                        )}
                                        <h2 className="text-xl leading-snug text-[var(--text)]">
                                            {hasDetail ? (
                                                <Link href={detailUrl} prefetch="hover" cacheFor="30s" className="transition-colors hover:text-[var(--primary)]">
                                                    {title}
                                                </Link>
                                            ) : external ? (
                                                <a href={external} target="_blank" rel="noopener noreferrer" className="transition-colors hover:text-[var(--primary)]">
                                                    {title}
                                                </a>
                                            ) : (
                                                title
                                            )}
                                        </h2>
                                        {excerpt && <p className="mt-3 line-clamp-3 text-sm text-[var(--text-muted)]">{excerpt}</p>}
                                        <div className="mt-5 pt-2">
                                            {hasDetail ? (
                                                <Link href={detailUrl} prefetch="hover" cacheFor="30s" className="inline-flex items-center gap-1.5 text-sm font-medium text-[var(--primary)] transition-colors hover:underline">
                                                    {tr('content.read_more', 'Read more')} →
                                                </Link>
                                            ) : external ? (
                                                <a href={external} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-1.5 text-sm font-medium text-[var(--primary)] transition-colors hover:underline">
                                                    {tr('content.view', 'View')} →
                                                </a>
                                            ) : null}
                                        </div>
                                    </div>
                                </article>
                            );
                        })}
                    </div>
                )}
            </section>
        </PublicLayout>
    );
}

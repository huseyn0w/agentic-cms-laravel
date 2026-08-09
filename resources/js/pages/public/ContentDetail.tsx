import { Link } from '@inertiajs/react';
import { PublicLayout } from '@/layouts/PublicLayout';
import type { Shell } from '@/layouts/PublicLayout';

interface FieldMeta {
    name: string;
    label: string;
    type: string;
    options: Record<string, string>;
}

type Item = Record<string, unknown> & { id: number };

interface ContentDetailProps {
    shell: Shell;
    slug: string;
    title: string;
    fields: FieldMeta[];
    item: Item;
    indexUrl: string;
    indexLabel: string;
}

/**
 * Generic public detail page for one content-type row. Renders each schema field
 * by type: image as a cover, url as an external link, richtext as prose, and the
 * remaining text/number/date/select fields as a small meta list. SEO head is
 * server-rendered by Blade.
 */
export default function ContentDetail({ shell, title, fields, item, indexUrl, indexLabel }: ContentDetailProps) {
    const str = (name: string): string | null => {
        const value = item[name];
        if (typeof value === 'string') return value !== '' ? value : null;
        if (typeof value === 'number') return String(value);
        return null;
    };

    const titleField = fields.find((f) => f.type === 'text');
    const imageField = fields.find((f) => f.type === 'image');
    const image = imageField ? str(imageField.name) : null;

    const richtextFields = fields.filter((f) => f.type === 'richtext');
    const urlFields = fields.filter((f) => f.type === 'url');
    const metaFields = fields.filter(
        (f) => ['text', 'number', 'date', 'select'].includes(f.type) && f.name !== titleField?.name,
    );

    const optionLabel = (f: FieldMeta, value: string): string => f.options[value] ?? value;

    return (
        <PublicLayout shell={shell}>
            <article className="mx-auto max-w-3xl px-5 py-12 sm:px-8 sm:py-16">
                <Link href={indexUrl} prefetch="hover" cacheFor="30s" className="inline-flex items-center gap-1.5 text-sm font-medium text-[var(--text-muted)] transition-colors hover:text-[var(--primary)]" data-testid="content-back">
                    ← {indexLabel}
                </Link>

                <h1 className="mt-6 text-[clamp(1.875rem,3vw,2.441rem)] font-medium leading-[1.15] tracking-[-0.01em] text-[var(--text)]">
                    {title}
                </h1>

                {metaFields.length > 0 && (
                    <dl className="mt-4 flex flex-wrap gap-x-6 gap-y-1 text-sm text-[var(--text-muted)]" data-testid="content-meta">
                        {metaFields.map((f) => {
                            const value = str(f.name);
                            if (!value) return null;
                            return (
                                <div key={f.name} className="flex gap-1.5">
                                    <dt className="font-medium text-[var(--text)]">{f.label}:</dt>
                                    <dd>{f.type === 'select' ? optionLabel(f, value) : value}</dd>
                                </div>
                            );
                        })}
                    </dl>
                )}

                {image && (
                    <img src={image} alt={title} decoding="async" className="mt-8 w-full rounded-lg border border-[var(--border)] object-cover" />
                )}

                {richtextFields.map((f) => {
                    const value = str(f.name);
                    if (!value) return null;
                    return (
                        <div
                            key={f.name}
                            className="prose prose-neutral mt-8 max-w-none text-[var(--text)]"
                            data-testid="content-body"
                            dangerouslySetInnerHTML={{ __html: value }}
                        />
                    );
                })}

                {urlFields.map((f) => {
                    const value = str(f.name);
                    if (!value) return null;
                    return (
                        <div key={f.name} className="mt-8">
                            <a href={value} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-1.5 text-sm font-medium text-[var(--primary)] transition-colors hover:underline">
                                {f.label} →
                            </a>
                        </div>
                    );
                })}
            </article>
        </PublicLayout>
    );
}

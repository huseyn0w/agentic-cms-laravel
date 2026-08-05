import { useTranslation } from 'react-i18next';
import { PublicLayout } from '@/layouts/PublicLayout';
import type { Shell } from '@/layouts/PublicLayout';

interface ServiceCard {
    title: string;
    url: string;
    icon: string | null;
    excerpt: string | null;
}

interface ServiceIndexProps {
    shell: Shell;
    heading: string;
    emptyText: string;
    services: ServiceCard[];
}

/**
 * Public services grid. SEO head + the ItemList JSON-LD are server-rendered by
 * Blade (seo-meta + the app-public jsonLd hook) — no <Head> here.
 */
export default function ServiceIndex({ shell, heading, emptyText, services }: ServiceIndexProps) {
    const { t } = useTranslation();
    const tr = (k: string, f: string) => (t(k) === k ? f : t(k));

    return (
        <PublicLayout shell={shell}>
            <header className="border-b border-[var(--border)] bg-[var(--surface-2)]">
                <div className="mx-auto max-w-7xl px-5 py-12 sm:px-8 sm:py-16">
                    <h1 className="font-serif text-[clamp(1.875rem,3vw,2.441rem)] font-medium leading-[1.15] tracking-[-0.01em] text-[var(--text)]">
                        {heading}
                    </h1>
                </div>
            </header>

            <section className="mx-auto max-w-7xl px-5 py-14 sm:px-8 sm:py-16">
                {services.length === 0 ? (
                    <div className="rounded-lg border border-dashed border-[var(--border)] px-6 py-16 text-center" data-testid="services-empty">
                        <p className="text-[var(--text-muted)]">{emptyText}</p>
                    </div>
                ) : (
                    <div className="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        {services.map((service) => (
                            <div key={service.url} className="rounded-lg border border-[var(--border)] bg-[var(--surface)] p-6 transition hover:border-[var(--border-strong)] hover:shadow-card" data-testid="service-card">
                                {service.icon && (
                                    <div className="mb-4 text-2xl leading-none" aria-hidden="true">
                                        {service.icon}
                                    </div>
                                )}
                                <h2 className="font-serif text-xl leading-snug text-[var(--text)]">
                                    <a href={service.url} className="transition-colors hover:text-[var(--primary)]">
                                        {service.title}
                                    </a>
                                </h2>
                                {service.excerpt && <p className="mt-3 line-clamp-3 text-sm text-[var(--text-muted)]">{service.excerpt}</p>}
                                <div className="mt-5">
                                    <a href={service.url} className="inline-flex items-center gap-1.5 text-sm font-medium text-[var(--primary)] transition-colors hover:underline">
                                        {tr('services.read_more', 'Read more')} →
                                    </a>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </section>
        </PublicLayout>
    );
}

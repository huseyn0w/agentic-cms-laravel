import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { RawHtml } from '@/components/RawHtml';
import { PublicLayout } from '@/layouts/PublicLayout';
import type { Shell } from '@/layouts/PublicLayout';
import type { SharedProps } from '@/lib/types';

type SearchType = 'post' | 'page' | 'user' | 'category' | 'tag';

interface SearchResults {
    query: string;
    type: SearchType;
    total: number;
    currentPage: number;
    lastPage: number;
    items: { label: string; url: string }[];
    pageBaseUrl: string;
}

interface SearchProps {
    shell: Shell;
    title: string;
    action: string;
    csrfToken: string;
    captchaHtml: string;
    results: SearchResults | null;
}

const FILTERS: { value: SearchType; key: string; fallback: string }[] = [
    { value: 'post', key: 'default/page.filter_post', fallback: 'Post' },
    { value: 'page', key: 'default/page.filter_page', fallback: 'Page' },
    { value: 'user', key: 'default/page.filter_user', fallback: 'User' },
    { value: 'category', key: 'default/page.filter_category', fallback: 'Category' },
    { value: 'tag', key: 'default/page.filter_tag', fallback: 'Tag' },
];

/**
 * Public search page. The query runs through a native POST form (so the
 * captcha's hidden input and CSRF token submit naturally); results and the
 * pretty paginated GET route render here. Search is noindex — SEO head stays
 * server-rendered by Blade, and there is no <Head> here.
 */
export default function Search({ shell, title, action, csrfToken, captchaHtml, results }: SearchProps) {
    const { t } = useTranslation();
    const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
    const { errors } = usePage<SharedProps>().props;
    const errorList = Object.values(errors ?? {});

    const isPostGrid = results?.type === 'post';
    const resultsWord = results && results.total === 1 ? tr('default/page.result', 'result') : tr('default/page.results', 'results');

    return (
        <PublicLayout shell={shell}>
            <section className="border-b border-[var(--border)] bg-[var(--surface-2)]">
                <div className="mx-auto max-w-[720px] px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
                    <h1 className="mb-8 text-center text-[clamp(1.875rem,3vw,2.441rem)] font-medium leading-[1.15] tracking-[-0.01em] text-[var(--text)]">
                        {title}
                    </h1>

                    <form action={action} method="post" className="space-y-5" noValidate>
                        <input type="hidden" name="_token" value={csrfToken} />

                        <div>
                            <label htmlFor="query" className="sr-only">
                                {tr('default/header.search', 'Search')}
                            </label>
                            <input
                                id="query"
                                type="text"
                                name="query"
                                required
                                defaultValue={results?.query ?? ''}
                                placeholder={tr('default/page.search_placeholder', 'Type keyword here and press ENTER')}
                                aria-invalid={errors?.query ? true : undefined}
                                className="w-full rounded-full border border-[var(--border-strong)] bg-[var(--surface)] px-6 py-4 text-lg text-[var(--text)] placeholder:text-[var(--text-subtle)] focus:border-[var(--ring)] focus:outline-none focus:ring-2 focus:ring-[var(--ring)]/30"
                            />
                        </div>

                        <div className="flex flex-col items-center gap-2 sm:flex-row sm:justify-center">
                            <label htmlFor="filter" className="text-sm font-medium text-[var(--text)]">
                                {tr('default/page.filter_by', 'Filter by:')}
                            </label>
                            <select
                                id="filter"
                                name="filter"
                                defaultValue={results?.type ?? 'post'}
                                aria-invalid={errors?.filter ? true : undefined}
                                className="rounded-full border border-[var(--border-strong)] bg-[var(--surface)] py-2 pl-4 pr-9 text-sm text-[var(--text)] focus:border-[var(--ring)] focus:outline-none focus:ring-2 focus:ring-[var(--ring)]/30"
                            >
                                {FILTERS.map((f) => (
                                    <option key={f.value} value={f.value}>
                                        {tr(f.key, f.fallback)}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {results && (
                            <p className="text-center font-mono text-xs text-[var(--text-muted)]">
                                {results.total} {resultsWord} {tr('default/page.found_for', 'found for')} &ldquo;{results.query}&rdquo;
                            </p>
                        )}

                        {captchaHtml.trim() !== '' && <RawHtml html={captchaHtml} />}

                        <div className="flex justify-center pt-1">
                            <button
                                type="submit"
                                className="inline-flex items-center gap-2 rounded-full bg-[var(--primary)] px-6 py-2.5 text-sm font-medium text-[var(--primary-contrast)] transition hover:opacity-90"
                            >
                                {tr('default/header.search', 'Search')}
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <section className="mx-auto max-w-[1080px] px-4 py-14 sm:px-6 sm:py-16 lg:px-8">
                {errorList.length > 0 && (
                    <div className="mb-6 rounded-md border border-[var(--error)]/40 bg-[var(--error)]/10 px-4 py-3 text-sm text-[var(--error)]" role="alert">
                        <ul className="list-disc space-y-1 pl-4">
                            {errorList.map((err) => (
                                <li key={err}>{err}</li>
                            ))}
                        </ul>
                    </div>
                )}

                {results && results.total > 0 && (
                    <>
                        <h2 className="mb-8 text-[clamp(1.563rem,2vw,1.953rem)] font-medium leading-[1.2] text-[var(--text)]">
                            {tr('default/page.search_result_headline', 'Results for')}: &ldquo;{results.query}&rdquo;
                        </h2>

                        {isPostGrid ? (
                            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                {results.items.map((item) => (
                                    <Link
                                        key={item.url}
                                        href={item.url}
                                        prefetch="hover"
                                        cacheFor="30s"
                                        data-testid="search-result"
                                        className="group flex flex-col rounded-lg border border-[var(--border)] bg-[var(--surface)] p-5 transition hover:border-[var(--border-strong)] hover:shadow-card"
                                    >
                                        <h3 className="text-lg font-medium text-[var(--text)] transition-colors group-hover:text-[var(--primary)]">
                                            {item.label}
                                        </h3>
                                    </Link>
                                ))}
                            </div>
                        ) : (
                            <ul className="divide-y divide-[var(--border)]">
                                {results.items.map((item) => (
                                    <li key={item.url}>
                                        <Link
                                            href={item.url}
                                            prefetch="hover"
                                            cacheFor="30s"
                                            data-testid="search-result"
                                            className="group flex items-center justify-between gap-4 py-5 transition-colors"
                                        >
                                            <h3 className="text-xl font-medium text-[var(--text)] transition-colors group-hover:text-[var(--primary)]">
                                                {item.label}
                                            </h3>
                                            <span aria-hidden="true" className="shrink-0 text-[var(--text-subtle)] transition-transform group-hover:translate-x-1 group-hover:text-[var(--primary)]">
                                                →
                                            </span>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}

                        {results.lastPage > 1 && (
                            <nav className="mt-12 flex flex-wrap items-center gap-2" aria-label="Pagination">
                                {Array.from({ length: results.lastPage }, (_, i) => i + 1).map((page) => (
                                    <Link
                                        key={page}
                                        href={`${results.pageBaseUrl}/page/${page}`}
                                        prefetch="hover"
                                        cacheFor="30s"
                                        className={
                                            'rounded-md px-3.5 py-2 text-sm transition-colors ' +
                                            (page === results.currentPage
                                                ? 'bg-[var(--primary)] text-[var(--primary-contrast)]'
                                                : 'text-[var(--text-muted)] hover:bg-[var(--surface-2)] hover:text-[var(--text)]')
                                        }
                                        aria-current={page === results.currentPage ? 'page' : undefined}
                                    >
                                        {page}
                                    </Link>
                                ))}
                            </nav>
                        )}
                    </>
                )}

                {results && results.total === 0 && (
                    <div className="rounded-lg border border-dashed border-[var(--border)] px-6 py-16 text-center" data-testid="search-empty">
                        <p className="font-sans text-[var(--text-muted)]">{tr('default/page.search_nothing_found', 'Nothing found')}</p>
                    </div>
                )}
            </section>
        </PublicLayout>
    );
}

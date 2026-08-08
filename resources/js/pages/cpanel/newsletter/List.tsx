import { Head, Link, router, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Pagination } from '@/components/admin/Pagination';
import { StatusPill } from '@/components/admin/StatusPill';
import type { PillTone } from '@/components/admin/StatusPill';
import type { Paginator } from '@/lib/types';
import type { FormEvent, ReactElement } from 'react';

interface Row {
  id: number;
  email: string;
  status: 'pending' | 'confirmed' | 'unsubscribed';
  locale: string | null;
  source: string;
  subscribed: string | null;
}
interface ListProps {
  subscribers: Paginator<Row>;
  filters: { status: string | null; search: string | null };
}

const BASE = '/agentic-cms-laravel-admin/newsletter';

const STATUS_META: Record<Row['status'], { tone: PillTone; key: string; fallback: string }> = {
  confirmed: { tone: 'success', key: 'cpanel/newsletter.status_confirmed', fallback: 'Confirmed' },
  pending: { tone: 'warning', key: 'cpanel/newsletter.status_pending', fallback: 'Pending' },
  unsubscribed: { tone: 'muted', key: 'cpanel/newsletter.status_unsubscribed', fallback: 'Unsubscribed' },
};

const FILTERS: Array<{ value: string | null; key: string; fallback: string }> = [
  { value: null, key: 'cpanel/newsletter.filter_all', fallback: 'All' },
  { value: 'confirmed', key: 'cpanel/newsletter.status_confirmed', fallback: 'Confirmed' },
  { value: 'pending', key: 'cpanel/newsletter.status_pending', fallback: 'Pending' },
  { value: 'unsubscribed', key: 'cpanel/newsletter.status_unsubscribed', fallback: 'Unsubscribed' },
];

export default function List({ subscribers, filters }: ListProps) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const rows = subscribers.data;

  const addForm = useForm({ email: '' });
  const searchForm = useForm({ search: filters.search ?? '' });

  const add = (e: FormEvent) => {
    e.preventDefault();
    addForm.post(BASE, { preserveScroll: true, onSuccess: () => addForm.reset('email') });
  };

  const search = (e: FormEvent) => {
    e.preventDefault();
    router.get(BASE, { status: filters.status ?? undefined, search: searchForm.data.search || undefined }, { preserveState: true });
  };

  const del = (id: number) => {
    if (!window.confirm(tr('cpanel/newsletter.delete_confirm', 'Delete this subscriber?'))) return;
    router.delete(`${BASE}/${id}`, { preserveScroll: true });
  };

  const filterHref = (status: string | null) =>
    `${BASE}?${new URLSearchParams({
      ...(status ? { status } : {}),
      ...(filters.search ? { search: filters.search } : {}),
    }).toString()}`;

  return (
    <>
      <Head title={tr('cpanel/newsletter.list_headline', 'Newsletter')} />
      <div className="mb-5 flex items-center justify-between gap-3">
        <h1 className="text-[22px] font-semibold tracking-tight">
          {tr('cpanel/newsletter.list_headline', 'Newsletter')}
        </h1>
        <a
          href={`${BASE}/export`}
          className="rounded-md border border-strong px-3 py-1.5 text-[12.5px] font-medium text-fg transition-colors hover:bg-surface-2"
          data-testid="newsletter-export"
        >
          {tr('cpanel/newsletter.export_button', 'Export CSV')}
        </a>
      </div>

      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form onSubmit={add} className="flex gap-2" data-testid="newsletter-add">
          <input
            type="email"
            required
            placeholder={tr('cpanel/newsletter.add_placeholder', 'new@example.com')}
            value={addForm.data.email}
            onChange={(e) => addForm.setData('email', e.target.value)}
            className="h-9 w-64 rounded-md border border-border bg-surface px-3 text-[13.5px] outline-none focus:border-strong"
            data-testid="newsletter-add-email"
          />
          <button type="submit" disabled={addForm.processing} className="rounded-md bg-fg px-3 py-1.5 text-[12.5px] font-medium text-bg">
            {tr('cpanel/newsletter.add_button', 'Add subscriber')}
          </button>
        </form>

        <form onSubmit={search} className="flex gap-2">
          <input
            type="search"
            placeholder={tr('cpanel/newsletter.search_placeholder', 'Search email…')}
            value={searchForm.data.search}
            onChange={(e) => searchForm.setData('search', e.target.value)}
            className="h-9 w-56 rounded-md border border-border bg-surface px-3 text-[13.5px] outline-none focus:border-strong"
            data-testid="newsletter-search"
          />
        </form>
      </div>

      <div className="mb-4 flex flex-wrap gap-1.5" data-testid="newsletter-filters">
        {FILTERS.map((f) => {
          const active = (filters.status ?? null) === f.value;
          return (
            <Link
              key={f.value ?? 'all'}
              href={filterHref(f.value)}
              data-testid={`filter-${f.value ?? 'all'}`}
              aria-current={active ? 'true' : undefined}
              className={`rounded-full px-3 py-1 text-[12.5px] font-medium transition-colors ${
                active ? 'bg-fg text-bg' : 'border border-border text-muted hover:bg-surface-2'
              }`}
            >
              {tr(f.key, f.fallback)}
            </Link>
          );
        })}
      </div>

      <div className="admin-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full border-collapse text-[13.5px]">
            <thead>
              <tr className="text-left text-[11px] uppercase tracking-wide text-faint">
                <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/newsletter.table_email', 'Email')}</th>
                <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/newsletter.table_status', 'Status')}</th>
                <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/newsletter.table_locale', 'Locale')}</th>
                <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/newsletter.table_source', 'Source')}</th>
                <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/newsletter.table_subscribed', 'Subscribed')}</th>
                <th className="w-[90px] border-b admin-sep px-4 py-2.5" />
              </tr>
            </thead>
            <tbody>
              {rows.length === 0 && (
                <tr>
                  <td colSpan={6} className="border-b admin-sep px-4 py-8 text-center text-muted">
                    {tr('cpanel/newsletter.not_found', 'No subscribers yet')}
                  </td>
                </tr>
              )}
              {rows.map((r) => {
                const meta = STATUS_META[r.status];
                return (
                  <tr key={r.id} className="transition-colors hover:bg-surface-2">
                    <td className="border-b admin-sep px-4 py-3 font-medium text-fg">{r.email}</td>
                    <td className="border-b admin-sep px-4 py-3">
                      <StatusPill tone={meta.tone} label={tr(meta.key, meta.fallback)} />
                    </td>
                    <td className="border-b admin-sep px-4 py-3 uppercase text-muted">{r.locale ?? '—'}</td>
                    <td className="border-b admin-sep px-4 py-3 text-muted">{r.source}</td>
                    <td className="whitespace-nowrap border-b admin-sep px-4 py-3 tabular-nums text-faint">{r.subscribed ?? '—'}</td>
                    <td className="border-b admin-sep px-4 py-3 text-right">
                      <button onClick={() => del(r.id)} className="text-[12.5px] text-muted hover:text-error">
                        {tr('cpanel/newsletter.delete', 'Delete')}
                      </button>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
        <Pagination meta={subscribers} />
      </div>
    </>
  );
}

List.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Newsletter">{page}</AdminLayout>
);

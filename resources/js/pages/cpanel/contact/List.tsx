import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Pagination } from '@/components/admin/Pagination';
import type { Paginator } from '@/lib/types';
import type { FormEvent, ReactElement } from 'react';

export interface Row {
  id: number;
  name: string;
  email: string;
  subject: string;
  read: boolean;
  received: string | null;
}
interface ListProps {
  submissions: Paginator<Row>;
  filters: { unread: boolean; search: string | null };
  unread_count: number;
}

const BASE = '/agentic-cms-laravel-admin/contact';

export default function List({ submissions, filters, unread_count }: ListProps) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const rows = submissions.data;
  const [search, setSearch] = useState(filters.search ?? '');

  const query = (over: Record<string, unknown>) =>
    router.get(BASE, {
      unread: filters.unread ? 1 : undefined,
      search: search || undefined,
      ...over,
    }, { preserveState: true, replace: true });

  const submitSearch = (e: FormEvent) => {
    e.preventDefault();
    query({});
  };

  const del = (id: number) => {
    if (!window.confirm(tr('cpanel/contact.delete_confirm', 'Delete this message?'))) return;
    router.delete(`${BASE}/${id}`, { preserveScroll: true });
  };

  return (
    <>
      <Head title={tr('cpanel/contact.headline', 'Contact messages')} />
      <div className="mx-auto flex max-w-4xl flex-col gap-5">
        <div className="flex items-center gap-3">
          <h1 className="text-[22px] font-semibold tracking-tight">
            {tr('cpanel/contact.headline', 'Contact messages')}
          </h1>
          {unread_count > 0 && (
            <span className="rounded-full bg-primary px-2 py-0.5 text-[11px] font-semibold text-primary-contrast tabular-nums">
              {tr('cpanel/contact.unread_badge', ':count unread').replace(':count', String(unread_count))}
            </span>
          )}
        </div>

        <div className="flex flex-wrap items-center gap-2">
          <div className="flex gap-1">
            <button type="button" onClick={() => query({ unread: undefined })}
              aria-current={!filters.unread ? 'true' : undefined}
              className={`rounded-md px-3 py-1.5 text-[13px] font-medium ${!filters.unread ? 'bg-primary text-primary-contrast' : 'text-muted hover:text-fg'}`}>
              {tr('cpanel/contact.filter_all', 'All')}
            </button>
            <button type="button" onClick={() => query({ unread: 1 })}
              aria-current={filters.unread ? 'true' : undefined}
              className={`rounded-md px-3 py-1.5 text-[13px] font-medium ${filters.unread ? 'bg-primary text-primary-contrast' : 'text-muted hover:text-fg'}`}>
              {tr('cpanel/contact.filter_unread', 'Unread')}
            </button>
          </div>
          <form onSubmit={submitSearch} className="ml-auto">
            <input value={search} onChange={(e) => setSearch(e.target.value)}
              placeholder={tr('cpanel/contact.search_placeholder', 'Search name, email, subject')}
              className="field-input w-64 max-w-full" data-testid="contact-search" />
          </form>
        </div>

        <div className="admin-card overflow-x-auto">
          {rows.length === 0 ? (
            <p className="p-6 text-[13px] text-muted">{tr('cpanel/contact.empty', 'No messages yet.')}</p>
          ) : (
            <table className="w-full text-[13px]">
              <thead>
                <tr className="text-left text-[11px] uppercase tracking-wide text-muted">
                  <th className="px-4 py-2.5 font-semibold">{tr('cpanel/contact.col_name', 'Name')}</th>
                  <th className="px-4 py-2.5 font-semibold">{tr('cpanel/contact.col_subject', 'Subject')}</th>
                  <th className="px-4 py-2.5 font-semibold">{tr('cpanel/contact.col_received', 'Received')}</th>
                  <th className="px-4 py-2.5" />
                </tr>
              </thead>
              <tbody>
                {rows.map((row) => (
                  <tr key={row.id} className={`border-t admin-sep ${row.read ? '' : 'font-semibold'}`} data-testid={`contact-row-${row.id}`}>
                    <td className="px-4 py-2.5">
                      {!row.read && <span className="mr-2 inline-block h-1.5 w-1.5 rounded-full bg-[color:var(--accent-blue)]" aria-hidden="true" />}
                      <Link href={`${BASE}/${row.id}`} className="hover:underline">{row.name}</Link>
                      <div className="text-[12px] font-normal text-muted">{row.email}</div>
                    </td>
                    <td className="px-4 py-2.5">
                      <Link href={`${BASE}/${row.id}`} className="hover:underline">{row.subject}</Link>
                    </td>
                    <td className="px-4 py-2.5 tabular-nums text-muted">{row.received ?? '—'}</td>
                    <td className="px-4 py-2.5 text-right">
                      <button type="button" onClick={() => del(row.id)}
                        className="text-[12px] text-muted hover:text-[color:var(--error)]" data-testid={`contact-delete-${row.id}`}>
                        {tr('cpanel/contact.delete', 'Delete')}
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>

        <Pagination meta={submissions} />
      </div>
    </>
  );
}

List.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Messages">{page}</AdminLayout>
);

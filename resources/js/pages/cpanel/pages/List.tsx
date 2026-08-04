import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import type { SharedProps } from '@/lib/types';
import type { ReactElement } from 'react';

interface Row {
  id: number;
  title: string;
  author: string | null;
  created_at: string;
  status: number;
}
interface ListProps {
  pages_list: { data: Row[]; current_page: number; last_page: number; total: number };
  trashed: boolean;
}

const BASE = '/agentic-cms-laravel-admin/pages';

export default function List({ pages_list, trashed }: ListProps) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const { locale } = usePage<SharedProps>().props;
  const rows = pages_list.data;
  const [selected, setSelected] = useState<number[]>([]);

  const ids = rows.map((r) => r.id);
  const allSelected = ids.length > 0 && ids.every((id) => selected.includes(id));
  const toggle = (id: number) =>
    setSelected((s) => (s.includes(id) ? s.filter((x) => x !== id) : [...s, id]));
  const toggleAll = () => setSelected(() => (allSelected ? [] : ids));

  const bulkDelete = (list: number[]) => {
    if (list.length === 0) return;
    if (!window.confirm(tr('cpanel/pages.js_delete_confirmation', 'Delete selected pages?'))) return;
    // PageListRequest requires a pages_action in {delete,destroy,restore};
    // the live-mode bulk delete is the 'delete' action.
    router.delete(`${BASE}/multipleDelete`, {
      data: { pages: list, pages_action: 'delete' },
      preserveScroll: true,
      onSuccess: () => setSelected([]),
    });
  };

  const bulkAction = (action: 'restore' | 'destroy', list: number[]) => {
    if (list.length === 0) return;
    const msg =
      action === 'destroy'
        ? tr('cpanel/pages.js_destroy_confirmation', 'Permanently delete selected pages?')
        : tr('cpanel/pages.js_restore_confirmation', 'Restore selected pages?');
    if (!window.confirm(msg)) return;
    router.post(
      `${BASE}/multiple`,
      { pages: list, pages_action: action },
      { preserveScroll: true, onSuccess: () => setSelected([]) },
    );
  };

  const tab = (isActive: boolean) =>
    `-mb-px border-b-2 px-4 py-2 text-[13px] transition ${
      isActive ? 'border-primary font-semibold text-fg' : 'border-transparent text-muted hover:text-fg'
    }`;

  return (
    <>
      <Head title={tr('cpanel/pages.general_pages', 'Pages')} />
      <div className="mb-5 flex items-center">
        <h1 className="text-[22px] font-semibold tracking-tight">
          {tr('cpanel/pages.general_pages', 'Pages')}
        </h1>
        {!trashed && (
          <Link href={`${BASE}/new`} prefetch="mount" cacheFor="15s"
            className="ml-auto inline-flex h-9 items-center gap-1.5 rounded-[10px] bg-primary px-3.5 text-[13px] font-semibold text-primary-contrast">
            + {tr('cpanel/pages.add_new_page', 'New page')}
          </Link>
        )}
      </div>

      <div className="mb-4 flex gap-1 border-b admin-sep">
        <Link href={BASE} prefetch cacheFor="15s" className={tab(!trashed)}>
          {tr('cpanel/pages.general_pages', 'Pages')}
        </Link>
        <Link href={`${BASE}/trashed`} prefetch cacheFor="15s" className={tab(trashed)}>
          {tr('cpanel/pages.trashed_pages', 'Trashed')}
        </Link>
      </div>

      <div className="admin-card overflow-hidden">
        {selected.length > 0 && (
          <div className="flex items-center gap-3 border-b admin-sep bg-surface-2 px-4 py-2.5 text-[12.5px]">
            {selected.length} {tr('cpanel/pages.selected', 'selected')}
            {trashed ? (
              <>
                <button data-testid="bulk-restore-confirm" onClick={() => bulkAction('restore', selected)}
                  className="ml-1 inline-flex items-center gap-1.5 font-semibold text-success">
                  {tr('cpanel/pages.bulk_action_restore_label', 'Restore')}
                </button>
                <button data-testid="bulk-destroy-confirm" onClick={() => bulkAction('destroy', selected)}
                  className="inline-flex items-center gap-1.5 font-semibold text-error">
                  {tr('cpanel/pages.bulk_action_destroy_label', 'Delete permanently')}
                </button>
              </>
            ) : (
              <button data-testid="bulk-delete-confirm" onClick={() => bulkDelete(selected)}
                className="ml-1 inline-flex items-center gap-1.5 font-semibold text-error">
                {tr('cpanel/pages.bulk_action_delete_label', 'Delete selected')}
              </button>
            )}
          </div>
        )}
        <table className="w-full border-collapse text-[13.5px]">
          <thead>
            <tr className="text-left text-[11px] uppercase tracking-wide text-faint">
              <th className="w-[38px] border-b admin-sep px-4 py-2.5">
                {ids.length > 0 && (
                  <input type="checkbox" aria-label="select-all" checked={allSelected} onChange={toggleAll} />
                )}
              </th>
              <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/pages.table_name', 'Title')}</th>
              <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/pages.table_author', 'Author')}</th>
              <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/pages.table_publish_date', 'Date')}</th>
              <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/pages.table_status', 'Status')}</th>
              <th className="w-[150px] border-b admin-sep px-4 py-2.5"></th>
            </tr>
          </thead>
          <tbody>
            {rows.length === 0 && (
              <tr>
                <td colSpan={6} className="border-b admin-sep px-4 py-8 text-center text-muted">
                  {tr('cpanel/pages.not_found', 'No pages yet')}
                </td>
              </tr>
            )}
            {rows.map((r) => (
              <tr key={r.id} className="hover:bg-black/[.022]">
                <td className="border-b admin-sep px-4 py-3">
                  <input type="checkbox" aria-label={`select-${r.id}`}
                    checked={selected.includes(r.id)} onChange={() => toggle(r.id)} />
                </td>
                <td className="border-b admin-sep px-4 py-3 font-semibold">{r.title}</td>
                <td className="border-b admin-sep px-4 py-3 text-muted">{r.author ?? '—'}</td>
                <td className="whitespace-nowrap border-b admin-sep px-4 py-3 text-muted">{r.created_at}</td>
                <td className="border-b admin-sep px-4 py-3">
                  {r.status === 1 ? (
                    <span className="text-success">{tr('cpanel/pages.page_published', 'Published')}</span>
                  ) : (
                    <span className="text-muted">{tr('cpanel/pages.page_pending', 'Private')}</span>
                  )}
                </td>
                <td className="border-b admin-sep px-4 py-3">
                  <div className="flex gap-3.5 text-[12.5px]">
                    {trashed ? (
                      <>
                        <button onClick={() => bulkAction('restore', [r.id])} className="text-muted hover:text-success">
                          {tr('cpanel/pages.restore_page', 'Restore')}
                        </button>
                        <button onClick={() => bulkAction('destroy', [r.id])} className="text-muted hover:text-error">
                          {tr('cpanel/pages.destroy_page', 'Delete permanently')}
                        </button>
                      </>
                    ) : (
                      <>
                        <Link href={`${BASE}/${r.id}/${locale.current}`} prefetch cacheFor="15s"
                          className="text-muted hover:text-fg">
                          {tr('cpanel/pages.edit_page', 'Edit')}
                        </Link>
                        <button onClick={() => bulkDelete([r.id])} className="text-muted hover:text-error">
                          {tr('cpanel/pages.delete_page', 'Delete')}
                        </button>
                      </>
                    )}
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        <div className="flex items-center px-4 py-3 text-[12.5px] text-muted">
          {rows.length} {tr('cpanel/pages.of', 'of')} {pages_list.total}
        </div>
      </div>
    </>
  );
}

List.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Pages">{page}</AdminLayout>
);

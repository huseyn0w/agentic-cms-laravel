import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Pagination } from '@/components/admin/Pagination';
import { StatusPill } from '@/components/admin/StatusPill';
import type { Paginator } from '@/lib/types';
import type { ReactElement } from 'react';

interface Row {
  id: number;
  post_title: string | null;
  comment: string;
  author: string | null;
  date: string;
  status: number;
}
interface ListProps {
  comments_list: Paginator<Row>;
}

const BASE = '/agentic-cms-laravel-admin/comments';

export default function List({ comments_list }: ListProps) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const rows = comments_list.data;
  const [selected, setSelected] = useState<number[]>([]);

  const ids = rows.map((r) => r.id);
  const allSelected = ids.length > 0 && ids.every((id) => selected.includes(id));
  const toggle = (id: number) =>
    setSelected((s) => (s.includes(id) ? s.filter((x) => x !== id) : [...s, id]));
  const toggleAll = () => setSelected(() => (allSelected ? [] : ids));

  const del = (list: number[]) => {
    if (list.length === 0) return;
    if (!window.confirm(tr('cpanel/comments.js_delete_confirmation', 'Delete selected comments?'))) return;
    router.delete(`${BASE}/multipleDelete`, {
      data: { comments: list },
      preserveScroll: true,
      onSuccess: () => setSelected([]),
    });
  };

  const approve = (id: number) => router.put(`${BASE}/${id}/approve`, {}, { preserveScroll: true });
  const unapprove = (id: number) => router.put(`${BASE}/${id}/unapprove`, {}, { preserveScroll: true });

  return (
    <>
      <Head title={tr('cpanel/comments.list_headline', 'Comments')} />
      <div className="mb-5 flex items-center">
        <h1 className="text-[22px] font-semibold tracking-tight">
          {tr('cpanel/comments.list_headline', 'Comments')}
        </h1>
      </div>

      <div className="admin-card overflow-hidden">
        {selected.length > 0 && (
          <div className="flex items-center gap-3 border-b admin-sep bg-surface-2 px-4 py-2.5 text-[12.5px]">
            {selected.length} {tr('cpanel/comments.selected', 'selected')}
            <button data-testid="bulk-delete-confirm" onClick={() => del(selected)}
              className="ml-1 inline-flex items-center gap-1.5 font-semibold text-error">
              {tr('cpanel/comments.bulk_action_delete_label', 'Delete selected')}
            </button>
          </div>
        )}
        <div className="overflow-x-auto">
          <table className="w-full border-collapse text-[13.5px]">
            <thead>
              <tr className="text-left text-[11px] uppercase tracking-wide text-faint">
                <th className="w-[38px] border-b admin-sep px-4 py-2.5">
                  {ids.length > 0 && (
                    <input type="checkbox" aria-label="select-all" checked={allSelected} onChange={toggleAll} />
                  )}
                </th>
                <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/comments.table_title', 'Post')}</th>
                <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/comments.table_name', 'Comment')}</th>
                <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/comments.table_author', 'Author')}</th>
                <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/comments.table_publish_date', 'Date')}</th>
                <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/comments.table_status', 'Status')}</th>
                <th className="w-[190px] border-b admin-sep px-4 py-2.5"></th>
              </tr>
            </thead>
            <tbody>
              {rows.length === 0 && (
                <tr>
                  <td colSpan={7} className="border-b admin-sep px-4 py-8 text-center text-muted">
                    {tr('cpanel/comments.not_found', 'No comments yet')}
                  </td>
                </tr>
              )}
              {rows.map((r) => (
                <tr key={r.id} className="transition-colors hover:bg-surface-2">
                  <td className="border-b admin-sep px-4 py-3">
                    <input type="checkbox" aria-label={`select-${r.id}`}
                      checked={selected.includes(r.id)} onChange={() => toggle(r.id)} />
                  </td>
                  <td className="border-b admin-sep px-4 py-3 text-muted">{r.post_title ?? '—'}</td>
                  <td className="max-w-[360px] border-b admin-sep px-4 py-3 text-fg">{r.comment}</td>
                  <td className="border-b admin-sep px-4 py-3 text-muted">{r.author ?? '—'}</td>
                  <td className="whitespace-nowrap border-b admin-sep px-4 py-3 tabular-nums text-faint">{r.date}</td>
                  <td className="border-b admin-sep px-4 py-3">
                    {r.status === 1 ? (
                      <StatusPill tone="success" label={tr('cpanel/comments.status_approved', 'Approved')} />
                    ) : (
                      <StatusPill tone="warning" label={tr('cpanel/comments.status_pending', 'Pending')} />
                    )}
                  </td>
                  <td className="border-b admin-sep px-4 py-3">
                    <div className="flex gap-3.5 text-[12.5px]">
                      {r.status === 1 ? (
                        <button onClick={() => unapprove(r.id)} className="text-muted hover:text-fg">
                          {tr('cpanel/comments.unapprove', 'Unapprove')}
                        </button>
                      ) : (
                        <button onClick={() => approve(r.id)} className="text-muted hover:text-fg">
                          {tr('cpanel/comments.approve', 'Approve')}
                        </button>
                      )}
                      <button onClick={() => del([r.id])} className="text-muted hover:text-error">
                        {tr('cpanel/comments.delete', 'Delete')}
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <Pagination meta={comments_list} />
      </div>
    </>
  );
}

List.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Comments">{page}</AdminLayout>
);

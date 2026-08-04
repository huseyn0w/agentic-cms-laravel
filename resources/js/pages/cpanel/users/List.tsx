import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Pagination } from '@/components/admin/Pagination';
import type { Paginator } from '@/lib/types';
import type { ReactElement } from 'react';

interface Row {
  id: number;
  username: string;
  email: string;
  name: string | null;
  surname: string | null;
  country: string | null;
  city: string | null;
  role: string | null;
}
interface ListProps {
  users_list: Paginator<Row>;
}

const BASE = '/agentic-cms-laravel-admin/users';

export default function List({ users_list }: ListProps) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const rows = users_list.data;
  const [selected, setSelected] = useState<number[]>([]);

  const ids = rows.map((r) => r.id);
  const allSelected = ids.length > 0 && ids.every((id) => selected.includes(id));
  const toggle = (id: number) =>
    setSelected((s) => (s.includes(id) ? s.filter((x) => x !== id) : [...s, id]));
  const toggleAll = () => setSelected(() => (allSelected ? [] : ids));

  // Users are hard-deleted (no trash). UserListRequest requires
  // users_action === 'delete'; the bulk endpoint handles single rows too.
  const del = (list: number[]) => {
    if (list.length === 0) return;
    if (!window.confirm(tr('cpanel/users.js_delete_confirmation', 'Delete selected users?'))) return;
    router.delete(`${BASE}/multipleDelete`, {
      data: { users: list, users_action: 'delete' },
      preserveScroll: true,
      onSuccess: () => setSelected([]),
    });
  };

  return (
    <>
      <Head title={tr('cpanel/users.list_headline', 'Users')} />
      <div className="mb-5 flex items-center">
        <h1 className="text-[22px] font-semibold tracking-tight">
          {tr('cpanel/users.list_headline', 'Users')}
        </h1>
        <Link href={`${BASE}/new`} prefetch="mount" cacheFor="15s"
          className="ml-auto inline-flex h-9 items-center gap-1.5 rounded-[10px] bg-primary px-3.5 text-[13px] font-semibold text-primary-contrast">
          + {tr('cpanel/users.add_new_user', 'New user')}
        </Link>
      </div>

      <div className="admin-card overflow-hidden">
        {selected.length > 0 && (
          <div className="flex items-center gap-3 border-b admin-sep bg-surface-2 px-4 py-2.5 text-[12.5px]">
            {selected.length} {tr('cpanel/users.selected', 'selected')}
            <button data-testid="bulk-delete-confirm" onClick={() => del(selected)}
              className="ml-1 inline-flex items-center gap-1.5 font-semibold text-error">
              {tr('cpanel/users.bulk_action_delete_label', 'Delete selected')}
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
                <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/users.table_username', 'Username')}</th>
                <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/users.table_email', 'Email')}</th>
                <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/users.table_name', 'Name')}</th>
                <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/users.table_surname', 'Surname')}</th>
                <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/users.table_country', 'Country')}</th>
                <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/users.table_city', 'City')}</th>
                <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/users.table_role', 'Role')}</th>
                <th className="w-[110px] border-b admin-sep px-4 py-2.5"></th>
              </tr>
            </thead>
            <tbody>
              {rows.length === 0 && (
                <tr>
                  <td colSpan={9} className="border-b admin-sep px-4 py-8 text-center text-muted">
                    {tr('cpanel/users.not_found', 'No users yet')}
                  </td>
                </tr>
              )}
              {rows.map((r) => (
                <tr key={r.id} className="hover:bg-black/[.022]">
                  <td className="border-b admin-sep px-4 py-3">
                    <input type="checkbox" aria-label={`select-${r.id}`}
                      checked={selected.includes(r.id)} onChange={() => toggle(r.id)} />
                  </td>
                  <td className="border-b admin-sep px-4 py-3 font-semibold">{r.username}</td>
                  <td className="border-b admin-sep px-4 py-3 text-muted">{r.email}</td>
                  <td className="border-b admin-sep px-4 py-3 text-muted">{r.name ?? '—'}</td>
                  <td className="border-b admin-sep px-4 py-3 text-muted">{r.surname ?? '—'}</td>
                  <td className="border-b admin-sep px-4 py-3 text-muted">{r.country ?? '—'}</td>
                  <td className="border-b admin-sep px-4 py-3 text-muted">{r.city ?? '—'}</td>
                  <td className="border-b admin-sep px-4 py-3">
                    <span className="rounded bg-surface-2 px-2 py-0.5 text-[11px] text-fg">{r.role ?? '—'}</span>
                  </td>
                  <td className="border-b admin-sep px-4 py-3">
                    <div className="flex gap-3.5 text-[12.5px]">
                      <Link href={`${BASE}/${r.id}`} prefetch cacheFor="15s" className="text-muted hover:text-fg">
                        {tr('cpanel/users.edit', 'Edit')}
                      </Link>
                      <button onClick={() => del([r.id])} className="text-muted hover:text-error">
                        {tr('cpanel/users.delete_user', 'Delete')}
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <Pagination meta={users_list} />
      </div>
    </>
  );
}

List.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Users">{page}</AdminLayout>
);

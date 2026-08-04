import { Head, Link, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Pagination } from '@/components/admin/Pagination';
import type { Paginator } from '@/lib/types';
import type { ReactElement } from 'react';

interface Row {
  id: number;
  name: string;
}
interface ListProps {
  roles_list: Paginator<Row>;
}

const BASE = '/agentic-cms-laravel-admin/roles';
// The seeded Administrator (1) and Editor (2) roles are not deletable, matching
// the legacy roles_list view guard.
const PROTECTED = [1, 2];

export default function List({ roles_list }: ListProps) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const rows = roles_list.data;

  const del = (id: number) => {
    if (!window.confirm(tr('cpanel/roles.js_delete_confirmation', 'Delete this role?'))) return;
    router.delete(`${BASE}/${id}/delete`, { preserveScroll: true });
  };

  return (
    <>
      <Head title={tr('cpanel/roles.list_headline', 'Roles')} />
      <div className="mb-5 flex items-center">
        <h1 className="text-[22px] font-semibold tracking-tight">
          {tr('cpanel/roles.list_headline', 'Roles')}
        </h1>
        <Link href={`${BASE}/new`} prefetch="mount" cacheFor="15s"
          className="ml-auto inline-flex h-9 items-center gap-1.5 rounded-[10px] bg-primary px-3.5 text-[13px] font-semibold text-primary-contrast">
          + {tr('cpanel/roles.add_new_role', 'New role')}
        </Link>
      </div>

      <div className="admin-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full border-collapse text-[13.5px]">
            <thead>
              <tr className="text-left text-[11px] uppercase tracking-wide text-faint">
                <th className="w-[52px] border-b admin-sep px-4 py-2.5">№</th>
                <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/roles.table_name', 'Role')}</th>
                <th className="w-[140px] border-b admin-sep px-4 py-2.5">{tr('cpanel/roles.table_action', 'Action')}</th>
              </tr>
            </thead>
            <tbody>
              {rows.length === 0 && (
                <tr>
                  <td colSpan={3} className="border-b admin-sep px-4 py-8 text-center text-muted">
                    {tr('cpanel/roles.not_found', 'No roles yet')}
                  </td>
                </tr>
              )}
              {rows.map((r, i) => (
                <tr key={r.id} className="hover:bg-black/[.022]">
                  <td className="border-b admin-sep px-4 py-3 text-faint">{i + 1}</td>
                  <td className="border-b admin-sep px-4 py-3 font-semibold">{r.name}</td>
                  <td className="border-b admin-sep px-4 py-3">
                    <div className="flex gap-3.5 text-[12.5px]">
                      <Link href={`${BASE}/${r.id}`} prefetch cacheFor="15s" className="text-muted hover:text-fg">
                        {tr('cpanel/roles.edit', 'Edit')}
                      </Link>
                      {!PROTECTED.includes(r.id) && (
                        <button onClick={() => del(r.id)} className="text-muted hover:text-error">
                          {tr('cpanel/roles.delete', 'Delete')}
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <Pagination meta={roles_list} />
      </div>
    </>
  );
}

List.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Roles">{page}</AdminLayout>
);

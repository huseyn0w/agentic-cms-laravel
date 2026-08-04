import { Head, Link, router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Pagination } from '@/components/admin/Pagination';
import type { Paginator, SharedProps } from '@/lib/types';
import type { ReactElement } from 'react';

interface Row {
  id: number;
  title: string;
}
interface ListProps {
  menus_list: Paginator<Row>;
}

const BASE = '/agentic-cms-laravel-admin/menus';

export default function List({ menus_list }: ListProps) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const locale = usePage<SharedProps>().props.locale.current;
  const rows = menus_list.data;

  const del = (id: number) => {
    if (!window.confirm(tr('cpanel/menus.js_delete_confirmation', 'Delete this menu?'))) return;
    router.delete(`${BASE}/${id}/delete`, { preserveScroll: true });
  };

  return (
    <>
      <Head title={tr('cpanel/menus.list_headline', 'Menus')} />
      <div className="mb-5 flex items-center">
        <h1 className="text-[22px] font-semibold tracking-tight">
          {tr('cpanel/menus.list_headline', 'Menus')}
        </h1>
        <Link href={`${BASE}/new`} className="ml-auto inline-flex h-9 items-center gap-1.5 rounded-[10px] bg-primary px-3.5 text-[13px] font-semibold text-primary-contrast">
          + {tr('cpanel/menus.add_new_menu', 'New menu')}
        </Link>
      </div>

      <div className="admin-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full border-collapse text-[13.5px]">
            <thead>
              <tr className="text-left text-[11px] uppercase tracking-wide text-faint">
                <th className="w-[52px] border-b admin-sep px-4 py-2.5">№</th>
                <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/menus.table_name', 'Name')}</th>
                <th className="w-[140px] border-b admin-sep px-4 py-2.5">{tr('cpanel/menus.table_action', 'Action')}</th>
              </tr>
            </thead>
            <tbody>
              {rows.length === 0 && (
                <tr>
                  <td colSpan={3} className="border-b admin-sep px-4 py-8 text-center text-muted">
                    {tr('cpanel/menus.not_found', 'No menus yet')}
                  </td>
                </tr>
              )}
              {rows.map((r, i) => (
                <tr key={r.id} className="hover:bg-black/[.022]">
                  <td className="border-b admin-sep px-4 py-3 text-faint">{i + 1}</td>
                  <td className="border-b admin-sep px-4 py-3 font-semibold">{r.title}</td>
                  <td className="border-b admin-sep px-4 py-3">
                    <div className="flex gap-3.5 text-[12.5px]">
                      <Link href={`${BASE}/${r.id}/${locale}`} className="text-muted hover:text-fg">
                        {tr('cpanel/menus.edit', 'Edit')}
                      </Link>
                      {r.id > 1 && (
                        <button onClick={() => del(r.id)} className="text-muted hover:text-error">
                          {tr('cpanel/menus.delete', 'Delete')}
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <Pagination meta={menus_list} />
      </div>
    </>
  );
}

List.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Menus">{page}</AdminLayout>
);

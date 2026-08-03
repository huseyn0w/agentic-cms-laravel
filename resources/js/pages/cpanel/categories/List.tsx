import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import type { SharedProps } from '@/lib/types';
import type { ReactElement } from 'react';

interface Row { id: number; title: string; slug: string; parent_title: string | null }
interface ListProps {
  categories_list: { data: Row[]; current_page: number; last_page: number; total: number };
}

const BASE = '/agentic-cms-laravel-admin/categories';
const PROTECTED_ID = 1; // seeded root category — never deletable

export default function List({ categories_list }: ListProps) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const { locale } = usePage<SharedProps>().props;
  const rows = categories_list.data;
  const [selected, setSelected] = useState<number[]>([]);

  const selectableIds = rows.filter((r) => r.id !== PROTECTED_ID).map((r) => r.id);
  const allSelected = selectableIds.length > 0 && selectableIds.every((id) => selected.includes(id));

  const toggle = (id: number) =>
    setSelected((s) => (s.includes(id) ? s.filter((x) => x !== id) : [...s, id]));

  const toggleAll = () =>
    setSelected((s) => (allSelected ? [] : selectableIds));

  const del = (ids: number[]) => {
    if (ids.length === 0) return;
    if (!window.confirm(tr('cpanel/categories.js_delete_confirmation', 'Delete selected categories?'))) return;
    router.delete(`${BASE}/multipleDelete`, {
      data: { categories: ids, categories_action: 'delete' },
      preserveScroll: true,
      onSuccess: () => setSelected([]),
    });
  };

  return (
    <>
      <Head title={tr('cpanel/menu.categories', 'Categories')} />
      <div className="mb-5 flex items-center">
        <div>
          <h1 className="text-[22px] font-semibold tracking-tight">{tr('cpanel/menu.categories', 'Categories')}</h1>
        </div>
        <Link href={`${BASE}/new`} prefetch="mount" cacheFor="15s"
          className="ml-auto inline-flex h-9 items-center gap-1.5 rounded-[10px] bg-primary px-3.5 text-[13px] font-semibold text-primary-contrast">
          + {tr('cpanel/categories.add_new', 'New category')}
        </Link>
      </div>

      <div className="admin-card overflow-hidden">
        {selected.length > 0 && (
          <div className="flex items-center gap-3 border-b admin-sep bg-surface-2 px-4 py-2.5 text-[12.5px]">
            {selected.length} {tr('cpanel/categories.selected', 'selected')}
            <button data-testid="bulk-delete-confirm" onClick={() => del(selected)}
              className="ml-1 inline-flex items-center gap-1.5 font-semibold text-error">
              {tr('cpanel/categories.delete_selected', 'Delete selected')}
            </button>
          </div>
        )}
        <table className="w-full border-collapse text-[13.5px]">
          <thead>
            <tr className="text-left text-[11px] uppercase tracking-wide text-faint">
              <th className="w-[38px] border-b admin-sep px-4 py-2.5">
                {selectableIds.length > 0 && (
                  <input type="checkbox" aria-label="select-all"
                    checked={allSelected} onChange={toggleAll} />
                )}
              </th>
              <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/categories.title', 'Title')}</th>
              <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/categories.slug', 'Slug')}</th>
              <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/categories.parent', 'Parent')}</th>
              <th className="w-[120px] border-b admin-sep px-4 py-2.5"></th>
            </tr>
          </thead>
          <tbody>
            {rows.length === 0 && (
              <tr>
                <td colSpan={5} className="border-b admin-sep px-4 py-8 text-center text-muted">
                  {tr('cpanel/categories.empty', 'No categories yet')}
                </td>
              </tr>
            )}
            {rows.map((r) => (
              <tr key={r.id} className="hover:bg-black/[.022]">
                <td className="border-b admin-sep px-4 py-3">
                  {r.id !== PROTECTED_ID && (
                    <input type="checkbox" aria-label={`select-${r.id}`}
                      checked={selected.includes(r.id)} onChange={() => toggle(r.id)} />
                  )}
                </td>
                <td className="border-b admin-sep px-4 py-3 font-semibold">{r.title}</td>
                <td className="border-b admin-sep px-4 py-3 font-mono text-xs text-muted">{r.slug}</td>
                <td className="border-b admin-sep px-4 py-3 text-muted">{r.parent_title ?? '—'}</td>
                <td className="border-b admin-sep px-4 py-3">
                  <div className="flex gap-3.5 text-[12.5px]">
                    <Link href={`${BASE}/${r.id}/${locale.current}`} prefetch cacheFor="15s"
                      className="text-muted hover:text-fg">{tr('cpanel/categories.edit', 'Edit')}</Link>
                    {r.id !== PROTECTED_ID && (
                      <button onClick={() => del([r.id])} className="text-muted hover:text-error">
                        {tr('cpanel/categories.delete', 'Delete')}
                      </button>
                    )}
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        <div className="flex items-center px-4 py-3 text-[12.5px] text-muted">
          {rows.length} {tr('cpanel/categories.of', 'of')} {categories_list.total}
        </div>
      </div>
    </>
  );
}

List.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Categories">{page}</AdminLayout>
);

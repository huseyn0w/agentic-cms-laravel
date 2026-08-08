import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Pagination } from '@/components/admin/Pagination';
import type { Paginator } from '@/lib/types';
import type { FormEvent, ReactElement } from 'react';

interface FieldDef {
  name: string;
  label: string;
  type: string;
  options: Record<string, string>;
}
interface ContentTypeDef {
  slug: string;
  label: string;
  fields: FieldDef[];
  columns: string[];
}
type Record_ = { id: number } & Record<string, unknown>;
interface Props {
  type: ContentTypeDef;
  records: Paginator<Record_>;
  filters: { search: string | null };
}

const BASE = '/agentic-cms-laravel-admin/content';

function cell(value: unknown): string {
  if (value === null || value === undefined) return '—';
  if (typeof value === 'boolean') return value ? '✓' : '—';
  const s = String(value).replace(/<[^>]+>/g, '');
  return s.length > 80 ? s.slice(0, 80) + '…' : s;
}

export default function Index({ type, records, filters }: Props) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const rows = records.data;
  const [search, setSearch] = useState(filters.search ?? '');
  const base = `${BASE}/${type.slug}`;
  const colLabel = (name: string) => type.fields.find((f) => f.name === name)?.label ?? name;

  const submitSearch = (e: FormEvent) => {
    e.preventDefault();
    router.get(base, { search: search || undefined }, { preserveState: true, replace: true });
  };

  const del = (id: number) => {
    if (!window.confirm(tr('cpanel/content.delete_confirm', 'Delete this item?'))) return;
    router.delete(`${base}/${id}`, { preserveScroll: true });
  };

  return (
    <>
      <Head title={type.label} />
      <div className="mx-auto flex max-w-4xl flex-col gap-5">
        <div className="flex items-center gap-3">
          <h1 className="text-[22px] font-semibold tracking-tight">{type.label}</h1>
          <Link href={`${base}/create`} data-testid="content-create"
            className="ml-auto rounded-md bg-primary px-3 py-1.5 text-[13px] font-semibold text-primary-contrast hover:bg-primary-hover">
            {tr('cpanel/content.add', 'Add new')}
          </Link>
        </div>

        <form onSubmit={submitSearch}>
          <input value={search} onChange={(e) => setSearch(e.target.value)}
            placeholder={tr('cpanel/content.search_placeholder', 'Search')}
            className="field-input w-72 max-w-full" data-testid="content-search" />
        </form>

        <div className="admin-card overflow-x-auto">
          {rows.length === 0 ? (
            <p className="p-6 text-[13px] text-muted">{tr('cpanel/content.empty', 'Nothing here yet.')}</p>
          ) : (
            <table className="w-full text-[13px]">
              <thead>
                <tr className="text-left text-[11px] uppercase tracking-wide text-muted">
                  {type.columns.map((c) => (
                    <th key={c} className="px-4 py-2.5 font-semibold">{colLabel(c)}</th>
                  ))}
                  <th className="px-4 py-2.5" />
                </tr>
              </thead>
              <tbody>
                {rows.map((row) => (
                  <tr key={row.id} className="border-t admin-sep" data-testid={`content-row-${row.id}`}>
                    {type.columns.map((c) => (
                      <td key={c} className="px-4 py-2.5">
                        <Link href={`${base}/${row.id}/edit`} className="hover:underline">{cell(row[c])}</Link>
                      </td>
                    ))}
                    <td className="px-4 py-2.5 text-right">
                      <button type="button" onClick={() => del(row.id)}
                        className="text-[12px] text-muted hover:text-[color:var(--error)]"
                        data-testid={`content-delete-${row.id}`}>
                        {tr('cpanel/content.delete', 'Delete')}
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>

        <Pagination meta={records} />
      </div>
    </>
  );
}

Index.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Content">{page}</AdminLayout>
);

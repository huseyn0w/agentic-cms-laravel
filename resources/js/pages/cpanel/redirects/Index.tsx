import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Pagination } from '@/components/admin/Pagination';
import { TextField } from '@/components/TextField';
import { Button } from '@/components/Button';
import type { Paginator } from '@/lib/types';
import type { FormEvent, ReactElement } from 'react';

interface Row {
  id: number;
  source_path: string;
  target: string;
  status_code: number;
  hits: number;
}
interface Props {
  redirects: Paginator<Row>;
  filters: { search: string | null };
}

const BASE = '/agentic-cms-laravel-admin/redirects';

export default function Index({ redirects, filters }: Props) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const rows = redirects.data;
  const [search, setSearch] = useState(filters.search ?? '');

  const form = useForm({ source_path: '', target: '', status_code: 301 });

  const add = (e: FormEvent) => {
    e.preventDefault();
    form.post(BASE, { preserveScroll: true, onSuccess: () => form.reset() });
  };

  const submitSearch = (e: FormEvent) => {
    e.preventDefault();
    router.get(BASE, { search: search || undefined }, { preserveState: true, replace: true });
  };

  const del = (id: number) => {
    if (!window.confirm(tr('cpanel/redirects.delete_confirm', 'Delete this redirect?'))) return;
    router.delete(`${BASE}/${id}`, { preserveScroll: true });
  };

  return (
    <>
      <Head title={tr('cpanel/redirects.headline', 'Redirects')} />
      <div className="mx-auto flex max-w-4xl flex-col gap-5">
        <div>
          <h1 className="text-[22px] font-semibold tracking-tight">
            {tr('cpanel/redirects.headline', 'Redirects')}
          </h1>
          <p className="mt-1 text-[13px] text-muted">
            {tr('cpanel/redirects.intro', 'Send old URLs to new ones with a 301/302.')}
          </p>
        </div>

        <form onSubmit={add} className="admin-card flex flex-col gap-3 p-[18px]">
          <div className="grid gap-3 sm:grid-cols-[1fr_1fr_auto]">
            <TextField name="source_path" label={tr('cpanel/redirects.source', 'Source path')}
              data-testid="redirect-source" value={form.data.source_path} error={form.errors.source_path}
              placeholder={tr('cpanel/redirects.source_placeholder', '/old-category/old-post')}
              onChange={(e) => form.setData('source_path', e.target.value)} />
            <TextField name="target" label={tr('cpanel/redirects.target', 'Target')}
              data-testid="redirect-target" value={form.data.target} error={form.errors.target}
              placeholder={tr('cpanel/redirects.target_placeholder', '/new-post or https://…')}
              onChange={(e) => form.setData('target', e.target.value)} />
            <div className="flex flex-col gap-y-1.5">
              <label htmlFor="status_code" className="font-sans text-sm font-medium text-fg">
                {tr('cpanel/redirects.status', 'Type')}
              </label>
              <select id="status_code" name="status_code" className="field-input"
                value={form.data.status_code}
                onChange={(e) => form.setData('status_code', Number(e.target.value))}>
                <option value={301}>{tr('cpanel/redirects.status_301', '301 permanent')}</option>
                <option value={302}>{tr('cpanel/redirects.status_302', '302 temporary')}</option>
              </select>
            </div>
          </div>
          <Button type="submit" variant="primary" size="md" loading={form.processing}
            data-testid="redirect-add" className="self-start">
            {tr('cpanel/redirects.add', 'Add redirect')}
          </Button>
        </form>

        <form onSubmit={submitSearch}>
          <input value={search} onChange={(e) => setSearch(e.target.value)}
            placeholder={tr('cpanel/redirects.search_placeholder', 'Search source or target')}
            className="field-input w-72 max-w-full" data-testid="redirect-search" />
        </form>

        <div className="admin-card overflow-x-auto">
          {rows.length === 0 ? (
            <p className="p-6 text-[13px] text-muted">{tr('cpanel/redirects.empty', 'No redirects yet.')}</p>
          ) : (
            <table className="w-full text-[13px]">
              <thead>
                <tr className="text-left text-[11px] uppercase tracking-wide text-muted">
                  <th className="px-4 py-2.5 font-semibold">{tr('cpanel/redirects.source', 'Source path')}</th>
                  <th className="px-4 py-2.5 font-semibold">{tr('cpanel/redirects.target', 'Target')}</th>
                  <th className="px-4 py-2.5 font-semibold">{tr('cpanel/redirects.status', 'Type')}</th>
                  <th className="px-4 py-2.5 font-semibold">{tr('cpanel/redirects.hits', 'Hits')}</th>
                  <th className="px-4 py-2.5" />
                </tr>
              </thead>
              <tbody>
                {rows.map((row) => (
                  <tr key={row.id} className="border-t admin-sep" data-testid={`redirect-row-${row.id}`}>
                    <td className="px-4 py-2.5 font-mono text-[12px]">{row.source_path}</td>
                    <td className="px-4 py-2.5 font-mono text-[12px] text-muted">{row.target}</td>
                    <td className="px-4 py-2.5 tabular-nums">{row.status_code}</td>
                    <td className="px-4 py-2.5 tabular-nums text-muted">{row.hits}</td>
                    <td className="px-4 py-2.5 text-right">
                      <button type="button" onClick={() => del(row.id)}
                        className="text-[12px] text-muted hover:text-[color:var(--error)]"
                        data-testid={`redirect-delete-${row.id}`}>
                        {tr('cpanel/redirects.delete', 'Delete')}
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>

        <Pagination meta={redirects} />
      </div>
    </>
  );
}

Index.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Settings / Redirects">{page}</AdminLayout>
);

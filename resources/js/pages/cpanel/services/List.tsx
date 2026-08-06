import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Pagination } from '@/components/admin/Pagination';
import { StatusPill } from '@/components/admin/StatusPill';
import type { Paginator, SharedProps } from '@/lib/types';
import type { ReactElement } from 'react';

interface Row {
  id: number;
  title: string;
  sort_order: number;
  status: number;
}
interface ListProps {
  services_list: Paginator<Row>;
  trashed: boolean;
}

const BASE = '/agentic-cms-laravel-admin/services';

export default function List({ services_list, trashed }: ListProps) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const { locale } = usePage<SharedProps>().props;
  const rows = services_list.data;
  const [selected, setSelected] = useState<number[]>([]);

  const ids = rows.map((r) => r.id);
  const allSelected = ids.length > 0 && ids.every((id) => selected.includes(id));
  const toggle = (id: number) =>
    setSelected((s) => (s.includes(id) ? s.filter((x) => x !== id) : [...s, id]));
  const toggleAll = () => setSelected(() => (allSelected ? [] : ids));

  // Services route every bulk/single mutation through one endpoint:
  // POST /multiple with services_action in {delete,restore,destroy}.
  const action = (services_action: 'delete' | 'restore' | 'destroy', list: number[]) => {
    if (list.length === 0) return;
    const confirmKey =
      services_action === 'delete'
        ? ['cpanel/services.js_delete_confirmation', 'Delete selected services?']
        : services_action === 'destroy'
          ? ['cpanel/services.js_destroy_confirmation', 'Permanently delete selected services?']
          : ['cpanel/services.js_restore_confirmation', 'Restore selected services?'];
    if (!window.confirm(tr(confirmKey[0], confirmKey[1]))) return;
    router.post(
      `${BASE}/multiple`,
      { services: list, services_action },
      { preserveScroll: true, onSuccess: () => setSelected([]) },
    );
  };

  const tab = (isActive: boolean) =>
    `-mb-px border-b-2 px-4 py-2 text-[13px] transition ${
      isActive
        ? 'border-[color:var(--accent-blue)] font-semibold text-fg'
        : 'border-transparent text-muted hover:text-fg'
    }`;

  return (
    <>
      <Head title={tr('cpanel/services.general_services', 'Services')} />
      <div className="mb-5 flex items-center">
        <h1 className="text-[22px] font-semibold tracking-tight">
          {tr('cpanel/services.general_services', 'Services')}
        </h1>
        {!trashed && (
          <Link href={`${BASE}/new`} prefetch="mount" cacheFor="15s"
            className="ml-auto inline-flex h-9 items-center gap-1.5 rounded-[10px] bg-primary px-3.5 text-[13px] font-semibold text-primary-contrast">
            + {tr('cpanel/services.add_new_service', 'New service')}
          </Link>
        )}
      </div>

      <div className="mb-4 flex gap-1 border-b admin-sep">
        <Link href={BASE} prefetch cacheFor="15s" className={tab(!trashed)}>
          {tr('cpanel/services.general_services', 'Services')}
        </Link>
        <Link href={`${BASE}/trashed`} prefetch cacheFor="15s" className={tab(trashed)}>
          {tr('cpanel/services.trashed_services', 'Trashed')}
        </Link>
      </div>

      <div className="admin-card overflow-hidden">
        {selected.length > 0 && (
          <div className="flex items-center gap-3 border-b admin-sep bg-surface-2 px-4 py-2.5 text-[12.5px]">
            {selected.length} {tr('cpanel/services.selected', 'selected')}
            {trashed ? (
              <>
                <button data-testid="bulk-restore-confirm" onClick={() => action('restore', selected)}
                  className="ml-1 inline-flex items-center gap-1.5 font-semibold text-success">
                  {tr('cpanel/services.bulk_action_restore_label', 'Restore')}
                </button>
                <button data-testid="bulk-destroy-confirm" onClick={() => action('destroy', selected)}
                  className="inline-flex items-center gap-1.5 font-semibold text-error">
                  {tr('cpanel/services.bulk_action_destroy_label', 'Delete permanently')}
                </button>
              </>
            ) : (
              <button data-testid="bulk-delete-confirm" onClick={() => action('delete', selected)}
                className="ml-1 inline-flex items-center gap-1.5 font-semibold text-error">
                {tr('cpanel/services.bulk_action_delete_label', 'Delete selected')}
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
              <th className="w-[70px] border-b admin-sep px-4 py-2.5">{tr('cpanel/services.table_order', 'Order')}</th>
              <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/services.table_title', 'Title')}</th>
              <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/services.table_status', 'Status')}</th>
              <th className="w-[150px] border-b admin-sep px-4 py-2.5"></th>
            </tr>
          </thead>
          <tbody>
            {rows.length === 0 && (
              <tr>
                <td colSpan={5} className="border-b admin-sep px-4 py-8 text-center text-muted">
                  {tr('cpanel/services.not_found', 'No services yet')}
                </td>
              </tr>
            )}
            {rows.map((r) => (
              <tr key={r.id} className="transition-colors hover:bg-surface-2">
                <td className="border-b admin-sep px-4 py-3">
                  <input type="checkbox" aria-label={`select-${r.id}`}
                    checked={selected.includes(r.id)} onChange={() => toggle(r.id)} />
                </td>
                <td className="border-b admin-sep px-4 py-3 tabular-nums text-faint">{r.sort_order}</td>
                <td className="border-b admin-sep px-4 py-3 font-medium tracking-tight">{r.title}</td>
                <td className="border-b admin-sep px-4 py-3">
                  {r.status === 1 ? (
                    <StatusPill tone="success" label={tr('cpanel/services.status_published', 'Published')} />
                  ) : (
                    <StatusPill tone="muted" label={tr('cpanel/services.status_private', 'Private')} />
                  )}
                </td>
                <td className="border-b admin-sep px-4 py-3">
                  <div className="flex gap-3.5 text-[12.5px]">
                    {trashed ? (
                      <>
                        <button onClick={() => action('restore', [r.id])} className="text-muted hover:text-success">
                          {tr('cpanel/services.restore', 'Restore')}
                        </button>
                        <button onClick={() => action('destroy', [r.id])} className="text-muted hover:text-error">
                          {tr('cpanel/services.destroy', 'Delete permanently')}
                        </button>
                      </>
                    ) : (
                      <>
                        <Link href={`${BASE}/${r.id}/${locale.current}`} prefetch cacheFor="15s"
                          className="font-medium text-muted hover:text-[color:var(--accent-blue)]">
                          {tr('cpanel/services.edit', 'Edit')}
                        </Link>
                        <button onClick={() => action('delete', [r.id])} className="text-muted hover:text-error">
                          {tr('cpanel/services.delete', 'Delete')}
                        </button>
                      </>
                    )}
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        <Pagination meta={services_list} />
      </div>
    </>
  );
}

List.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Services">{page}</AdminLayout>
);

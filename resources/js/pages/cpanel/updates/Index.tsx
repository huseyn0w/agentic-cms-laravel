import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Button } from '@/components/Button';
import type { ReactElement } from 'react';

interface Release {
  version: string;
  notes?: string | null;
}
interface HistoryRow {
  id: number;
  from_version: string | null;
  to_version: string | null;
  status: string;
  message: string | null;
  finished_at: string | null;
}
interface Props {
  current_version: string;
  available: Release | null;
  history: HistoryRow[];
}

const ENDPOINT = '/agentic-cms-laravel-admin/updates';

const STATUS_TONE: Record<string, string> = {
  success: 'text-[color:var(--success)]',
  failed: 'text-[color:var(--error)]',
  rolled_back: 'text-[color:var(--warning)]',
  pending: 'text-muted',
};

export default function Index({ current_version, available, history }: Props) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const [checking, setChecking] = useState(false);
  const [updating, setUpdating] = useState(false);

  const check = () => {
    setChecking(true);
    router.post(`${ENDPOINT}/check`, {}, { preserveScroll: true, onFinish: () => setChecking(false) });
  };

  const runUpdate = () => {
    if (!window.confirm(tr('cpanel/updates.confirm', 'Run the core update now? The site will briefly enter maintenance mode.'))) {
      return;
    }
    setUpdating(true);
    router.post(`${ENDPOINT}/run`, {}, { preserveScroll: true, onFinish: () => setUpdating(false) });
  };

  return (
    <>
      <Head title={tr('cpanel/updates.headline', 'Updates')} />
      <div className="mx-auto flex max-w-3xl flex-col gap-6">
        <h1 className="text-[22px] font-semibold tracking-tight">
          {tr('cpanel/updates.headline', 'Updates')}
        </h1>

        <section className="admin-card flex flex-col gap-4 p-[18px]">
          <div className="flex items-center gap-4">
            <div>
              <div className="text-[12px] font-semibold uppercase tracking-wide text-muted">
                {tr('cpanel/updates.current_version', 'Current version')}
              </div>
              <div className="text-lg font-semibold tabular-nums" data-testid="current-version">
                v{current_version}
              </div>
            </div>
            <Button type="button" variant="secondary" size="md" onClick={check} loading={checking}
              data-testid="check-updates" className="ml-auto">
              {tr('cpanel/updates.check', 'Check for updates')}
            </Button>
          </div>

          {available ? (
            <div className="flex items-center gap-3 rounded-md bg-surface-2 px-3.5 py-3" data-testid="available">
              <span className="text-[13px] text-fg">
                {tr('cpanel/updates.available', 'Update available')}:{' '}
                <span className="font-semibold tabular-nums">v{available.version}</span>
              </span>
              <Button type="button" variant="primary" size="md" onClick={runUpdate} loading={updating}
                data-testid="run-update" className="ml-auto">
                {tr('cpanel/updates.update_now', 'Update now')}
              </Button>
            </div>
          ) : (
            <p className="text-[13px] text-muted" data-testid="up-to-date">
              {tr('cpanel/updates.up_to_date', 'You are on the latest version.')}
            </p>
          )}
        </section>

        <section className="admin-card flex flex-col gap-3 p-[18px]">
          <h2 className="text-[11px] font-semibold uppercase tracking-wide text-muted">
            {tr('cpanel/updates.history', 'History')}
          </h2>
          {history.length === 0 ? (
            <p className="text-[13px] text-muted">{tr('cpanel/updates.no_history', 'No updates yet.')}</p>
          ) : (
            <table className="w-full text-[13px]">
              <tbody>
                {history.map((row) => (
                  <tr key={row.id} className="border-t admin-sep">
                    <td className="py-2 tabular-nums text-muted">{row.finished_at ?? '—'}</td>
                    <td className="py-2 tabular-nums">
                      {row.from_version ? `v${row.from_version}` : '—'} → {row.to_version ? `v${row.to_version}` : '—'}
                    </td>
                    <td className={`py-2 font-medium ${STATUS_TONE[row.status] ?? 'text-fg'}`}>
                      {tr(`cpanel/updates.status_${row.status}`, row.status)}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </section>
      </div>
    </>
  );
}

Index.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Settings / Updates">{page}</AdminLayout>
);

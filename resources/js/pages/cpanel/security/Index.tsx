import { Head, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Pagination } from '@/components/admin/Pagination';
import { StatusPill, type PillTone } from '@/components/admin/StatusPill';
import type { Paginator } from '@/lib/types';
import type { ReactElement } from 'react';

interface Row {
  id: number;
  action: string;
  description: string | null;
  actor: string | null;
  ip: string | null;
  when: string | null;
}
interface Props {
  audit_log: Paginator<Row>;
  filter: string | null;
  actions: string[];
}

const BASE = '/agentic-cms-laravel-admin/security';

const TONES: Record<string, PillTone> = {
  login: 'success',
  login_failed: 'warning',
  logout: 'muted',
  lockout: 'warning',
};
const LABELS: Record<string, string> = {
  login: 'Sign in',
  login_failed: 'Failed sign in',
  logout: 'Sign out',
  lockout: 'Lockout',
};

export default function Index({ audit_log, filter, actions }: Props) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const rows = audit_log.data;

  const chip = (active: boolean) =>
    `cursor-pointer rounded-full border px-3 py-1 text-[12px] font-medium transition ${
      active
        ? 'border-transparent bg-primary text-primary-contrast'
        : 'border-border bg-surface text-muted hover:text-fg'
    }`;

  const label = (action: string) =>
    tr(`cpanel/security.action_${action}`, LABELS[action] ?? action);

  return (
    <>
      <Head title={tr('cpanel/menu.security', 'Security')} />
      <div className="mb-5">
        <h1 className="text-[22px] font-semibold tracking-tight">{tr('cpanel/menu.security', 'Security')}</h1>
        <p className="mt-1 text-[13px] text-subtle">
          {tr('cpanel/security.audit_subtitle', 'Authentication activity — sign-ins, failed attempts and lockouts.')}
        </p>
      </div>

      <div className="mb-4 flex flex-wrap gap-2">
        <Link href={BASE} prefetch cacheFor="15s" className={chip(!filter)}>
          {tr('cpanel/security.filter_all', 'All')}
        </Link>
        {actions.map((a) => (
          <Link key={a} href={`${BASE}?action=${a}`} prefetch cacheFor="15s" className={chip(filter === a)}>
            {label(a)}
          </Link>
        ))}
      </div>

      <div className="admin-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full border-collapse text-[13.5px]">
            <thead>
              <tr className="text-left text-[11px] uppercase tracking-wide text-faint">
                <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/security.col_when', 'When')}</th>
                <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/security.col_action', 'Event')}</th>
                <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/security.col_actor', 'Account')}</th>
                <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/security.col_ip', 'IP')}</th>
                <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/security.col_detail', 'Detail')}</th>
              </tr>
            </thead>
            <tbody>
              {rows.length === 0 && (
                <tr>
                  <td colSpan={5} className="border-b admin-sep px-4 py-8 text-center text-muted">
                    {tr('cpanel/security.empty', 'No activity recorded yet')}
                  </td>
                </tr>
              )}
              {rows.map((r) => (
                <tr key={r.id} className="transition-colors hover:bg-surface-2">
                  <td className="whitespace-nowrap border-b admin-sep px-4 py-3 tabular-nums text-faint">{r.when ?? '—'}</td>
                  <td className="border-b admin-sep px-4 py-3">
                    <StatusPill tone={TONES[r.action] ?? 'muted'} label={label(r.action)} />
                  </td>
                  <td className="border-b admin-sep px-4 py-3 font-medium tracking-tight">{r.actor ?? '—'}</td>
                  <td className="whitespace-nowrap border-b admin-sep px-4 py-3 font-mono text-xs text-faint">{r.ip ?? '—'}</td>
                  <td className="border-b admin-sep px-4 py-3 text-muted">{r.description ?? '—'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <Pagination meta={audit_log} />
      </div>
    </>
  );
}

Index.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Security">{page}</AdminLayout>
);

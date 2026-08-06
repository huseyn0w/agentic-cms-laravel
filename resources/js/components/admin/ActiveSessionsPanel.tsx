import { router, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { TextField } from '@/components/TextField';
import { Button } from '@/components/Button';
import type { FormEvent } from 'react';

export interface SessionRow {
  id: string;
  ip: string | null;
  device: string;
  last_active: string;
  is_current: boolean;
}

const BASE = '/agentic-cms-laravel-admin/myprofile';

export function ActiveSessionsPanel({ sessions }: { sessions: SessionRow[] }) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));

  const logoutForm = useForm({ password: '' });
  const hasOthers = sessions.some((s) => !s.is_current);

  const revoke = (id: string) => router.delete(`${BASE}/sessions/${id}`, { preserveScroll: true });

  const logoutOthers = (e: FormEvent) => {
    e.preventDefault();
    logoutForm.post(`${BASE}/sessions/logout-others`, {
      preserveScroll: true,
      onSuccess: () => logoutForm.reset('password'),
    });
  };

  return (
    <section className="admin-card flex flex-col gap-4 p-[18px]" data-testid="active-sessions">
      <div>
        <h3 className="text-[11px] font-semibold uppercase tracking-wide text-muted">
          {tr('cpanel/sessions.headline', 'Active sessions')}
        </h3>
        <p className="mt-1 text-[13px] text-subtle">
          {tr('cpanel/sessions.subtitle', 'Browsers currently signed in to your account. Revoke any you do not recognise.')}
        </p>
      </div>

      <div className="overflow-x-auto">
        <table className="w-full border-collapse text-[13.5px]">
          <thead>
            <tr className="text-left text-[11px] uppercase tracking-wide text-faint">
              <th className="border-b admin-sep px-3 py-2">{tr('cpanel/sessions.col_device', 'Device')}</th>
              <th className="border-b admin-sep px-3 py-2">{tr('cpanel/sessions.col_ip', 'IP')}</th>
              <th className="border-b admin-sep px-3 py-2">{tr('cpanel/sessions.col_last_active', 'Last active')}</th>
              <th className="border-b admin-sep px-3 py-2" />
            </tr>
          </thead>
          <tbody>
            {sessions.map((s) => (
              <tr key={s.id} data-testid="session-row" className="transition-colors hover:bg-surface-2">
                <td className="border-b admin-sep px-3 py-2.5 font-medium tracking-tight">
                  {s.device}
                  {s.is_current && (
                    <span className="ml-2 rounded-full border border-border bg-surface px-2 py-0.5 text-[11px] text-muted" data-testid="session-current">
                      {tr('cpanel/sessions.current', 'This device')}
                    </span>
                  )}
                </td>
                <td className="whitespace-nowrap border-b admin-sep px-3 py-2.5 font-mono text-xs text-faint">{s.ip ?? '—'}</td>
                <td className="whitespace-nowrap border-b admin-sep px-3 py-2.5 text-muted">{s.last_active}</td>
                <td className="border-b admin-sep px-3 py-2.5 text-right">
                  {!s.is_current && (
                    <button
                      type="button"
                      onClick={() => revoke(s.id)}
                      data-testid={`session-revoke-${s.id}`}
                      className="rounded-md border border-strong px-3 py-1 text-[12px] font-medium text-fg transition-colors hover:bg-surface-2"
                    >
                      {tr('cpanel/sessions.revoke', 'Revoke')}
                    </button>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {hasOthers && (
        <form onSubmit={logoutOthers} className="flex flex-col gap-3 border-t admin-sep pt-4">
          <TextField
            type="password"
            name="password"
            label={tr('cpanel/sessions.password_label', 'Current password')}
            data-testid="sessions-password"
            value={logoutForm.data.password}
            error={logoutForm.errors.password}
            onChange={(e) => logoutForm.setData('password', e.target.value)}
          />
          <div>
            <Button type="submit" variant="outline" size="md" loading={logoutForm.processing} data-testid="sessions-logout-others">
              {tr('cpanel/sessions.logout_others', 'Log out all other sessions')}
            </Button>
          </div>
        </form>
      )}
    </section>
  );
}

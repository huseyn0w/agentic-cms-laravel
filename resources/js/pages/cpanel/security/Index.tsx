import { Head, Link, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Pagination } from '@/components/admin/Pagination';
import { StatusPill, type PillTone } from '@/components/admin/StatusPill';
import { TextField } from '@/components/TextField';
import { Button } from '@/components/Button';
import type { Paginator } from '@/lib/types';
import type { FormEvent, ReactElement } from 'react';

interface Row {
  id: number;
  action: string;
  description: string | null;
  actor: string | null;
  ip: string | null;
  when: string | null;
}
interface SecuritySettings {
  login_throttle_enabled: boolean;
  login_max_attempts: number;
  login_decay_minutes: number;
  login_block_enabled: boolean;
  login_block_threshold: number;
  login_block_minutes: number;
  require_2fa_for_admins: boolean;
  password_min_length: number;
  password_require_mixed_case: boolean;
  password_require_numbers: boolean;
  password_require_symbols: boolean;
  password_check_hibp: boolean;
}
interface Props {
  audit_log: Paginator<Row>;
  filter: string | null;
  actions: string[];
  security_settings: SecuritySettings;
}

const BASE = '/agentic-cms-laravel-admin/security';
const SAVE = `${BASE}/settings`;

const TONES: Record<string, PillTone> = {
  login: 'success',
  login_failed: 'warning',
  logout: 'muted',
  lockout: 'warning',
  '2fa_enabled': 'success',
  '2fa_disabled': 'muted',
  '2fa_failed': 'warning',
};
const LABELS: Record<string, string> = {
  login: 'Sign in',
  login_failed: 'Failed sign in',
  logout: 'Sign out',
  lockout: 'Lockout',
  '2fa_enabled': '2FA enabled',
  '2fa_disabled': '2FA disabled',
  '2fa_failed': 'Failed 2FA',
};

export default function Index({ audit_log, filter, actions, security_settings }: Props) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const rows = audit_log.data;

  const form = useForm<SecuritySettings>({
    login_throttle_enabled: Boolean(security_settings.login_throttle_enabled),
    login_max_attempts: security_settings.login_max_attempts ?? 5,
    login_decay_minutes: security_settings.login_decay_minutes ?? 1,
    login_block_enabled: Boolean(security_settings.login_block_enabled),
    login_block_threshold: security_settings.login_block_threshold ?? 10,
    login_block_minutes: security_settings.login_block_minutes ?? 60,
    require_2fa_for_admins: Boolean(security_settings.require_2fa_for_admins),
    password_min_length: security_settings.password_min_length ?? 8,
    password_require_mixed_case: Boolean(security_settings.password_require_mixed_case),
    password_require_numbers: Boolean(security_settings.password_require_numbers),
    password_require_symbols: Boolean(security_settings.password_require_symbols),
    password_check_hibp: Boolean(security_settings.password_check_hibp),
  });

  const testid = (name: keyof SecuritySettings) => `security-${String(name).replace(/_/g, '-')}`;

  // Login throttle/block fields grey out with their master toggle; the password
  // fields are always editable.
  const disabledFor = (name: keyof SecuritySettings) => {
    if (String(name).startsWith('login_block')) return !form.data.login_block_enabled;
    if (String(name).startsWith('login_')) return !form.data.login_throttle_enabled;
    return false;
  };

  const number = (name: keyof SecuritySettings, labelKey: string, fallback: string) => (
    <TextField
      type="number"
      min={1}
      name={name}
      label={tr(labelKey, fallback)}
      data-testid={testid(name)}
      value={String(form.data[name] ?? '')}
      error={form.errors[name]}
      disabled={disabledFor(name)}
      onChange={(e) => form.setData(name, Number(e.target.value) as never)}
    />
  );

  const toggle = (
    name: 'login_throttle_enabled' | 'login_block_enabled' | 'require_2fa_for_admins'
      | 'password_require_mixed_case' | 'password_require_numbers' | 'password_require_symbols' | 'password_check_hibp',
    labelKey: string,
    fallback: string,
  ) => (
    <label className="flex cursor-pointer items-center gap-2.5 text-sm text-fg">
      <input
        type="checkbox"
        name={name}
        aria-label={name}
        data-testid={testid(name)}
        checked={form.data[name]}
        onChange={(e) => form.setData(name, e.target.checked)}
      />
      {tr(labelKey, fallback)}
    </label>
  );

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(SAVE, { preserveScroll: true });
  };

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

      <form onSubmit={submit} className="admin-card mb-6 flex flex-col gap-5 p-[18px]">
        <div className="flex items-center gap-4">
          <div>
            <h2 className="text-[15px] font-semibold tracking-tight">
              {tr('cpanel/security.protection_headline', 'Login protection')}
            </h2>
            <p className="mt-0.5 text-[12px] text-subtle">
              {tr('cpanel/security.protection_subtitle', 'Rate-limit failed sign-in attempts by email and IP.')}
            </p>
          </div>
          <Button type="submit" variant="primary" size="md" loading={form.processing}
            data-testid="security-submit" className="ml-auto">
            {tr('cpanel/security.save_button', 'Save')}
          </Button>
        </div>

        <div className="flex flex-col gap-4">
          {toggle('login_throttle_enabled', 'cpanel/security.throttle_enabled', 'Throttle failed sign-in attempts')}
          <div className="grid gap-4 sm:grid-cols-2">
            {number('login_max_attempts', 'cpanel/security.max_attempts', 'Max attempts before lockout')}
            {number('login_decay_minutes', 'cpanel/security.decay_minutes', 'Lockout duration (minutes)')}
          </div>
        </div>

        <div className="flex flex-col gap-4 border-t admin-sep pt-4">
          {toggle('login_block_enabled', 'cpanel/security.block_enabled', 'Auto-block repeat offenders for longer')}
          <div className="grid gap-4 sm:grid-cols-2">
            {number('login_block_threshold', 'cpanel/security.block_threshold', 'Attempts before auto-block')}
            {number('login_block_minutes', 'cpanel/security.block_minutes', 'Auto-block duration (minutes)')}
          </div>
        </div>

        <div className="flex flex-col gap-4 border-t admin-sep pt-4">
          {toggle('require_2fa_for_admins', 'cpanel/security.require_2fa', 'Require 2FA for everyone with admin access')}
        </div>

        <div className="flex flex-col gap-4 border-t admin-sep pt-4">
          <div>
            <h3 className="text-[13px] font-semibold tracking-tight">
              {tr('cpanel/security.password_policy_headline', 'Password policy')}
            </h3>
            <p className="mt-0.5 text-[12px] text-subtle">
              {tr('cpanel/security.password_policy_subtitle', 'Applied when accounts set or reset a password.')}
            </p>
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            {number('password_min_length', 'cpanel/security.password_min_length', 'Minimum length')}
          </div>
          {toggle('password_require_mixed_case', 'cpanel/security.password_mixed_case', 'Require upper and lower case')}
          {toggle('password_require_numbers', 'cpanel/security.password_numbers', 'Require a number')}
          {toggle('password_require_symbols', 'cpanel/security.password_symbols', 'Require a symbol')}
          {toggle('password_check_hibp', 'cpanel/security.password_hibp', 'Reject passwords found in known data breaches')}
        </div>
      </form>

      <h2 className="mb-3 text-[15px] font-semibold tracking-tight">
        {tr('cpanel/security.activity_headline', 'Activity log')}
      </h2>

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

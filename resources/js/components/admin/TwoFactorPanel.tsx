import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { TextField } from '@/components/TextField';
import { Button } from '@/components/Button';
import type { FormEvent } from 'react';

export interface TwoFactor {
  status: 'disabled' | 'pending' | 'enabled';
  is_self: boolean;
  setup: { secret: string; qr_svg: string } | null;
  recovery_codes: string[] | null;
}

const A = '/two-factor';

export function TwoFactorPanel({ two_factor }: { two_factor: TwoFactor }) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));

  const enableForm = useForm({});
  const confirmForm = useForm({ code: '' });
  const disableForm = useForm({ password: '' });

  if (!two_factor?.is_self) return null;

  const enable = (e: FormEvent) => { e.preventDefault(); enableForm.post(`${A}/enable`, { preserveScroll: true }); };
  const confirm = (e: FormEvent) => { e.preventDefault(); confirmForm.post(`${A}/confirm`, { preserveScroll: true }); };
  const disable = (e: FormEvent) => { e.preventDefault(); disableForm.delete(A, { preserveScroll: true }); };

  return (
    <section className="admin-card flex flex-col gap-4 p-[18px]">
      <div>
        <h3 className="text-[11px] font-semibold uppercase tracking-wide text-muted">
          {tr('cpanel/twofactor.headline', 'Two-factor authentication')}
        </h3>
        <p className="mt-1 text-[13px] text-subtle">
          {tr('cpanel/twofactor.subtitle', 'Protect your account with a time-based code from an authenticator app.')}
        </p>
      </div>

      {two_factor.status === 'disabled' && (
        <form onSubmit={enable}>
          <Button type="submit" variant="primary" size="md" data-testid="twofactor-enable" loading={enableForm.processing}>
            {tr('cpanel/twofactor.enable', 'Enable 2FA')}
          </Button>
        </form>
      )}

      {two_factor.status === 'pending' && two_factor.setup && (
        <form onSubmit={confirm} className="flex flex-col gap-3">
          <div className="max-w-[200px]" dangerouslySetInnerHTML={{ __html: two_factor.setup.qr_svg }} />
          <p className="text-[12px] text-subtle">
            {tr('cpanel/twofactor.manual_key', 'Manual key')}: <code className="font-mono">{two_factor.setup.secret}</code>
          </p>
          <TextField name="code" label={tr('cpanel/twofactor.confirm_label', 'Enter the 6-digit code')}
            data-testid="twofactor-confirm-code" value={confirmForm.data.code} error={confirmForm.errors.code}
            onChange={(e) => confirmForm.setData('code', e.target.value)} />
          <Button type="submit" variant="primary" size="md" loading={confirmForm.processing}>
            {tr('cpanel/twofactor.confirm', 'Confirm')}
          </Button>
        </form>
      )}

      {two_factor.status === 'enabled' && (
        <form onSubmit={disable} className="flex flex-col gap-3">
          <p className="text-[13px] text-success">{tr('cpanel/twofactor.active', '2FA is active on your account.')}</p>
          <TextField type="password" name="password" label={tr('cpanel/twofactor.password_label', 'Current password')}
            value={disableForm.data.password} error={disableForm.errors.password}
            onChange={(e) => disableForm.setData('password', e.target.value)} />
          <Button type="submit" variant="secondary" size="md" data-testid="twofactor-disable" loading={disableForm.processing}>
            {tr('cpanel/twofactor.disable', 'Disable 2FA')}
          </Button>
        </form>
      )}

      {two_factor.recovery_codes && (
        <div className="rounded-md border admin-sep bg-surface-2 p-3" data-testid="twofactor-recovery">
          <p className="mb-2 text-[12px] font-medium">{tr('cpanel/twofactor.recovery_headline', 'Recovery codes — store them safely')}</p>
          <ul className="grid grid-cols-2 gap-1 font-mono text-[12px]">
            {two_factor.recovery_codes.map((c) => <li key={c}>{c}</li>)}
          </ul>
        </div>
      )}
    </section>
  );
}

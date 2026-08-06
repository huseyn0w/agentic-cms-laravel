import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/Button';
import { TextField } from '@/components/TextField';
import { AuthLayout } from '@/layouts/AuthLayout';

export default function TwoFactorChallenge() {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const { data, setData, post, processing, errors } = useForm({ code: '' });

  function submit(e: FormEvent) {
    e.preventDefault();
    post('/two-factor/challenge');
  }

  return (
    <AuthLayout
      title={tr('cpanel/twofactor.challenge_headline', 'Two-factor authentication')}
      subtitle={tr('cpanel/twofactor.challenge_subtitle', 'Enter the code from your authenticator app, or a recovery code.')}
    >
      <Head title={tr('cpanel/twofactor.challenge_headline', 'Two-factor authentication')} />

      <form onSubmit={submit} className="space-y-5" noValidate>
        <TextField
          name="code"
          type="text"
          label={tr('cpanel/twofactor.confirm_label', 'Enter the 6-digit code')}
          value={data.code}
          onChange={(e) => setData('code', e.target.value)}
          error={errors.code}
          autoComplete="one-time-code"
          autoFocus
          required
          data-testid="twofactor-challenge-code"
        />
        <p className="text-[12px] text-subtle">
          {tr('cpanel/twofactor.challenge_recovery_hint', 'Lost your device? Enter one of your recovery codes instead.')}
        </p>
        <Button type="submit" variant="primary" loading={processing} data-testid="twofactor-challenge-submit">
          {tr('cpanel/twofactor.challenge_submit', 'Verify')}
        </Button>
      </form>
    </AuthLayout>
  );
}

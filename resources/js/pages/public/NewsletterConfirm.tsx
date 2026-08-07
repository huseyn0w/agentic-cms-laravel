import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { PublicLayout } from '@/layouts/PublicLayout';
import type { Shell } from '@/layouts/PublicLayout';

type ConfirmStatus = 'confirmed' | 'already' | 'invalid';

interface Props {
  shell: Shell;
  status: ConfirmStatus;
}

const COPY: Record<ConfirmStatus, { title: string; body: string }> = {
  confirmed: { title: 'default/newsletter.confirm_confirmed_title', body: 'default/newsletter.confirm_confirmed_body' },
  already: { title: 'default/newsletter.confirm_already_title', body: 'default/newsletter.confirm_already_body' },
  invalid: { title: 'default/newsletter.confirm_invalid_title', body: 'default/newsletter.confirm_invalid_body' },
};

export default function NewsletterConfirm({ shell, status }: Props) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const copy = COPY[status] ?? COPY.invalid;

  return (
    <PublicLayout shell={shell}>
      <Head title={tr(copy.title, 'Newsletter')} />
      <section className="mx-auto max-w-[42rem] px-5 py-24 text-center sm:px-8" data-testid="newsletter-confirm">
        <h1 className="text-3xl font-semibold tracking-tight text-[var(--text)]" data-testid="confirm-title">
          {tr(copy.title, 'Newsletter')}
        </h1>
        <p className="mt-4 text-[var(--text-subtle)]">{tr(copy.body, '')}</p>
        <a href={shell.homeUrl} className="btn-accent mt-8 inline-flex h-10 items-center px-5 text-sm font-medium">
          {tr('default/header.home', 'Home')}
        </a>
      </section>
    </PublicLayout>
  );
}

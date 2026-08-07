import { Head, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { PublicLayout } from '@/layouts/PublicLayout';
import type { Shell } from '@/layouts/PublicLayout';
import type { FormEvent } from 'react';

type UnsubStatus = 'done' | 'invalid';

interface Props {
  shell: Shell;
  status: UnsubStatus;
  token: string | null;
}

export default function NewsletterUnsubscribe({ shell, status, token }: Props) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const form = useForm({ token: token ?? '' });

  const resubscribe = (e: FormEvent) => {
    e.preventDefault();
    form.post('/newsletter/resubscribe', { preserveScroll: true });
  };

  const title = status === 'done' ? 'default/newsletter.unsub_done_title' : 'default/newsletter.unsub_invalid_title';
  const body = status === 'done' ? 'default/newsletter.unsub_done_body' : 'default/newsletter.unsub_invalid_body';

  return (
    <PublicLayout shell={shell}>
      <Head title={tr(title, 'Newsletter')} />
      <section className="mx-auto max-w-[42rem] px-5 py-24 text-center sm:px-8" data-testid="newsletter-unsubscribe">
        <h1 className="text-3xl font-semibold tracking-tight text-[var(--text)]" data-testid="unsub-title">
          {tr(title, 'Newsletter')}
        </h1>
        <p className="mt-4 text-[var(--text-subtle)]">{tr(body, '')}</p>

        {status === 'done' && token && (
          <form onSubmit={resubscribe} className="mt-8">
            <button type="submit" disabled={form.processing} className="btn-accent inline-flex h-10 items-center px-5 text-sm font-medium" data-testid="resubscribe">
              {tr('default/newsletter.unsub_resubscribe_button', 'Re-subscribe')}
            </button>
          </form>
        )}
      </section>
    </PublicLayout>
  );
}

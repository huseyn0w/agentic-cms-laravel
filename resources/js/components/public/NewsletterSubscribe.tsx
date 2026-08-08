import { useForm, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import type { FormEvent } from 'react';

interface Flash {
  newsletter_status?: string | null;
}

/**
 * Footer newsletter subscribe widget. Posts an email (plus a hidden honeypot)
 * to /newsletter/subscribe and swaps itself for a thank-you line once the server
 * flashes newsletter_status=submitted. Styled with public tokens (.btn-accent).
 */
export function NewsletterSubscribe() {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const { flash } = usePage<{ flash: Flash }>().props;

  const form = useForm({ email: '', website: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post('/newsletter/subscribe', {
      preserveScroll: true,
      onSuccess: () => form.reset('email'),
    });
  };

  const submitted = flash?.newsletter_status === 'submitted';

  return (
    <div className="max-w-sm" data-testid="newsletter-widget">
      <h3 className="text-[13px] font-semibold uppercase tracking-wide text-[var(--text)]">
        {tr('default/newsletter.widget_heading', 'Subscribe to the newsletter')}
      </h3>
      <p className="mt-1 text-sm text-[var(--text-subtle)]">
        {tr('default/newsletter.widget_subtitle', 'Occasional updates. No spam. Unsubscribe anytime.')}
      </p>

      {submitted ? (
        <p className="mt-4 text-sm font-medium text-[var(--accent)]" data-testid="newsletter-submitted">
          {tr('default/newsletter.widget_submitted', 'Thanks — check your inbox to confirm your subscription.')}
        </p>
      ) : (
        <form onSubmit={submit} className="mt-4 flex gap-2" data-testid="newsletter-form">
          {/* Honeypot: hidden from users, bots fill it. */}
          <input
            type="text"
            name="website"
            tabIndex={-1}
            autoComplete="off"
            aria-hidden="true"
            className="hidden"
            value={form.data.website}
            onChange={(e) => form.setData('website', e.target.value)}
          />
          <input
            type="email"
            name="email"
            required
            placeholder={tr('default/newsletter.widget_placeholder', 'you@example.com')}
            aria-label={tr('default/newsletter.widget_heading', 'Subscribe to the newsletter')}
            value={form.data.email}
            onChange={(e) => form.setData('email', e.target.value)}
            data-testid="newsletter-email"
            className="h-10 flex-1 rounded-md border border-[var(--border)] bg-[var(--bg)] px-3 text-sm text-[var(--text)] outline-none focus:border-[var(--accent)] focus:ring-2 focus:ring-[var(--ring)]"
          />
          <button
            type="submit"
            disabled={form.processing}
            className="btn-accent h-10 shrink-0 px-4 text-sm font-medium"
            data-testid="newsletter-submit"
          >
            {tr('default/newsletter.widget_button', 'Subscribe')}
          </button>
        </form>
      )}
      {form.errors.email && (
        <p className="mt-2 text-xs text-[var(--danger,#dc2626)]" data-testid="newsletter-error">
          {form.errors.email}
        </p>
      )}
    </div>
  );
}

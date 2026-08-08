import { Head, Link, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import type { ReactElement } from 'react';

interface Submission {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  subject: string;
  message: string;
  ip: string | null;
  received: string | null;
}
interface Props {
  submission: Submission;
}

const BASE = '/agentic-cms-laravel-admin/contact';

export default function Show({ submission }: Props) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const name = `${submission.first_name} ${submission.last_name}`.trim();

  const del = () => {
    if (!window.confirm(tr('cpanel/contact.delete_confirm', 'Delete this message?'))) return;
    router.delete(`${BASE}/${submission.id}`);
  };

  const mailto = `mailto:${submission.email}?subject=${encodeURIComponent('Re: ' + submission.subject)}`;

  return (
    <>
      <Head title={submission.subject} />
      <div className="mx-auto flex max-w-2xl flex-col gap-5">
        <Link href={BASE} className="text-[13px] text-muted hover:text-fg">
          ← {tr('cpanel/contact.back', 'Back to inbox')}
        </Link>

        <section className="admin-card flex flex-col gap-4 p-[18px]">
          <div>
            <h1 className="text-[20px] font-semibold tracking-tight">{submission.subject}</h1>
            <p className="mt-1 text-[13px] text-muted">
              {tr('cpanel/contact.from', 'From')}: <span className="text-fg">{name}</span>{' '}
              <a href={mailto} className="text-[color:var(--accent-blue)] hover:underline">{submission.email}</a>
            </p>
            <p className="text-[12px] tabular-nums text-muted">
              {submission.received ?? '—'}{submission.ip ? ` · ${submission.ip}` : ''}
            </p>
          </div>

          <div>
            <div className="mb-1 text-[11px] font-semibold uppercase tracking-wide text-muted">
              {tr('cpanel/contact.message_label', 'Message')}
            </div>
            <p className="whitespace-pre-wrap text-[14px] leading-relaxed text-fg">{submission.message}</p>
          </div>

          <div className="flex items-center gap-2 border-t admin-sep pt-4">
            <a href={mailto}
              className="rounded-md bg-primary px-3 py-1.5 text-[13px] font-semibold text-primary-contrast hover:bg-primary-hover">
              {tr('cpanel/contact.reply', 'Reply by email')}
            </a>
            <button type="button" onClick={del} data-testid="contact-delete"
              className="ml-auto rounded-md px-3 py-1.5 text-[13px] text-muted hover:text-[color:var(--error)]">
              {tr('cpanel/contact.delete', 'Delete')}
            </button>
          </div>
        </section>
      </div>
    </>
  );
}

Show.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Messages">{page}</AdminLayout>
);

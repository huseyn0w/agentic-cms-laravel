import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { useTranslation } from 'react-i18next';
import { PublicLayout } from '@/layouts/PublicLayout';
import type { Shell } from '@/layouts/PublicLayout';
import type { SharedProps } from '@/lib/types';

interface ContactProps {
    shell: Shell;
    title: string;
    crumbs: { label: string; url: string | null }[];
    action: string;
    csrfToken: string;
    captchaHtml: string;
    prefill: { first_name: string; last_name: string; email: string } | null;
}

/**
 * Injects a server-rendered HTML snippet and re-executes any <script> it
 * carries (innerHTML alone never runs scripts). Used for the captcha widget,
 * which is empty when no keys are configured.
 */
function RawHtml({ html }: { html: string }) {
    const ref = useRef<HTMLDivElement>(null);
    useEffect(() => {
        const host = ref.current;
        if (!host) return;
        host.innerHTML = html;
        host.querySelectorAll('script').forEach((old) => {
            const script = document.createElement('script');
            [...old.attributes].forEach((attr) => script.setAttribute(attr.name, attr.value));
            script.textContent = old.textContent;
            old.replaceWith(script);
        });
    }, [html]);
    return <div ref={ref} />;
}

/**
 * Contact page. A native form POSTs to sendMail (a full-page submit that
 * redirects back), so the captcha's hidden input is submitted naturally and
 * validation errors + the success flash arrive via Inertia shared props. SEO
 * head is server-rendered by Blade.
 */
export default function Contact({ shell, title, crumbs, action, csrfToken, captchaHtml, prefill }: ContactProps) {
    const { t } = useTranslation();
    const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
    const { errors, flash } = usePage<SharedProps>().props;
    const errorList = Object.values(errors ?? {});

    const fieldClass =
        'w-full rounded-sm border border-[var(--border-strong)] bg-[var(--surface)] px-3 py-2.5 text-base text-[var(--text)] placeholder:text-[var(--text-subtle)] focus:border-[var(--ring)] focus:outline-none focus:ring-2 focus:ring-[var(--ring)]/30';

    return (
        <PublicLayout shell={shell}>
            <header className="border-b border-[var(--border)] bg-[var(--surface-2)]">
                <div className="mx-auto max-w-[720px] px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
                    <nav aria-label="Breadcrumb" className="mb-3 flex flex-wrap items-center gap-1.5 font-mono text-xs text-[var(--text-muted)]">
                        {crumbs.map((crumb, i) => (
                            <span key={crumb.label} className="flex items-center gap-1.5">
                                {i > 0 && <span aria-hidden="true">/</span>}
                                {crumb.url ? (
                                    <a href={crumb.url} className="transition-colors hover:text-[var(--text)]">
                                        {crumb.label}
                                    </a>
                                ) : (
                                    <span className="text-[var(--text)]">{crumb.label}</span>
                                )}
                            </span>
                        ))}
                    </nav>
                    <h1 className="font-serif text-[clamp(2.25rem,4vw,3.052rem)] font-medium leading-[1.08] tracking-[-0.01em] text-[var(--text)]">{title}</h1>
                </div>
            </header>

            <section className="mx-auto max-w-[720px] px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
                {errorList.length > 0 && (
                    <div className="mb-6 rounded-md border border-[var(--error)]/40 bg-[var(--error)]/10 px-4 py-3 text-sm text-[var(--error)]" role="alert">
                        <ul className="list-disc space-y-1 pl-4">
                            {errorList.map((err) => (
                                <li key={err}>{err}</li>
                            ))}
                        </ul>
                    </div>
                )}

                {flash?.success && (
                    <div className="mb-6 rounded-md border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3 text-sm text-[var(--text)]" role="status">
                        {flash.success}
                    </div>
                )}

                <h2 className="mb-8 font-serif text-2xl font-medium text-[var(--text)]">{tr('default/page.have_question', 'Have a question?')}</h2>

                <form action={action} method="post" className="space-y-5" noValidate>
                    <input type="hidden" name="_token" value={csrfToken} />

                    {prefill ? (
                        <>
                            <input type="hidden" name="first_name" value={prefill.first_name} />
                            <input type="hidden" name="last_name" value={prefill.last_name} />
                            <input type="hidden" name="email" value={prefill.email} />
                            <Field id="subject" label={tr('default/page.subject', 'Subject')}>
                                <input id="subject" type="text" name="subject" required placeholder={tr('default/page.subject', 'Subject')} className={fieldClass} />
                            </Field>
                        </>
                    ) : (
                        <>
                            <div className="grid gap-5 sm:grid-cols-2">
                                <Field id="first_name" label={tr('default/page.first_name', 'First name')}>
                                    <input id="first_name" type="text" name="first_name" required placeholder={tr('default/page.first_name', 'First name')} className={fieldClass} />
                                </Field>
                                <Field id="last_name" label={tr('default/page.last_name', 'Last name')}>
                                    <input id="last_name" type="text" name="last_name" required placeholder={tr('default/page.last_name', 'Last name')} className={fieldClass} />
                                </Field>
                            </div>
                            <div className="grid gap-5 sm:grid-cols-2">
                                <Field id="email" label={tr('default/page.email', 'Email')}>
                                    <input id="email" type="email" name="email" required placeholder={tr('default/page.email', 'Email')} className={fieldClass} />
                                </Field>
                                <Field id="subject" label={tr('default/page.subject', 'Subject')}>
                                    <input id="subject" type="text" name="subject" required placeholder={tr('default/page.subject', 'Subject')} className={fieldClass} />
                                </Field>
                            </div>
                        </>
                    )}

                    <Field id="message" label={tr('default/page.message', 'Message')}>
                        <textarea id="message" name="message" rows={6} required placeholder={tr('default/page.message', 'Message')} className={`${fieldClass} resize-y`} />
                    </Field>

                    {captchaHtml.trim() !== '' && <RawHtml html={captchaHtml} />}

                    <div className="pt-2">
                        <button type="submit" className="inline-flex items-center gap-2 rounded-md bg-[var(--primary)] px-5 py-2.5 text-sm font-medium text-[var(--primary-contrast)] transition hover:opacity-90">
                            {tr('default/page.submit', 'Submit')} →
                        </button>
                    </div>
                </form>
            </section>
        </PublicLayout>
    );
}

function Field({ id, label, children }: { id: string; label: string; children: React.ReactNode }) {
    return (
        <div>
            <label htmlFor={id} className="mb-1.5 block text-sm font-medium text-[var(--text)]">
                {label}
            </label>
            {children}
        </div>
    );
}

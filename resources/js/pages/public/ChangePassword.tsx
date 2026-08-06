import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { RawHtml } from '@/components/RawHtml';
import { PublicLayout } from '@/layouts/PublicLayout';
import type { Shell } from '@/layouts/PublicLayout';
import type { SharedProps } from '@/lib/types';

interface ChangePasswordProps {
    shell: Shell;
    title: string;
    crumbs: { label: string; url: string | null }[];
    action: string;
    csrfToken: string;
    captchaHtml: string;
}

const fieldClass =
    'w-full rounded-sm border border-[var(--border-strong)] bg-[var(--surface)] px-3 py-2.5 text-base text-[var(--text)] focus:border-[var(--ring)] focus:outline-none focus:ring-2 focus:ring-[var(--ring)]/30';

/**
 * Self-service change-password form. A native PUT (method-spoofed) form so the
 * captcha's hidden input submits naturally; a wrong current password comes back
 * as a shared validation error, success as a flash. Noindex — SEO head Blade.
 */
export default function ChangePassword({ shell, title, crumbs, action, csrfToken, captchaHtml }: ChangePasswordProps) {
    const { t } = useTranslation();
    const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
    const { errors, flash } = usePage<SharedProps>().props;
    const errorList = Object.values(errors ?? {});

    return (
        <PublicLayout shell={shell}>
            <header className="border-b border-[var(--border)] bg-[var(--surface-2)]">
                <div className="mx-auto max-w-[480px] px-5 py-12 sm:px-8 sm:py-16">
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

            <section className="mx-auto max-w-[480px] px-5 py-16 sm:px-8 sm:py-20">
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

                <form action={action} method="post" className="space-y-5" noValidate>
                    <input type="hidden" name="_method" value="PUT" />
                    <input type="hidden" name="_token" value={csrfToken} />

                    <Field id="current_password" label={tr('default/change_password.current_password', 'Current password')}>
                        <input id="current_password" type="password" name="current_password" required autoComplete="current-password" className={fieldClass} />
                    </Field>
                    <Field id="password" label={tr('default/change_password.new_password', 'New password')}>
                        <input id="password" type="password" name="password" required autoComplete="new-password" className={fieldClass} />
                    </Field>
                    <Field id="password_confirmation" label={tr('default/change_password.confirm_new_password', 'Confirm new password')}>
                        <input id="password_confirmation" type="password" name="password_confirmation" required autoComplete="new-password" className={fieldClass} />
                    </Field>

                    {captchaHtml.trim() !== '' && <RawHtml html={captchaHtml} />}

                    <div className="flex justify-end pt-2">
                        <button type="submit" className="inline-flex items-center gap-2 rounded-md bg-[var(--primary)] px-5 py-2.5 text-sm font-medium text-[var(--primary-contrast)] transition hover:opacity-90">
                            {tr('default/change_password.change_password', 'Change password')}
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

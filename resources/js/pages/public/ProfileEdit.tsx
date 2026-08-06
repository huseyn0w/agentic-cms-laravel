import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { PublicLayout } from '@/layouts/PublicLayout';
import type { Shell } from '@/layouts/PublicLayout';
import type { SharedProps } from '@/lib/types';

interface ProfileFields {
    username: string | null;
    email: string | null;
    name: string | null;
    surname: string | null;
    country: string | null;
    city: string | null;
    about_me: string | null;
    gender: string | null;
    facebook_url: string | null;
    google_url: string | null;
    twitter_url: string | null;
    instagram_url: string | null;
    linkedin_url: string | null;
    xing_url: string | null;
}

interface ProfileEditProps {
    shell: Shell;
    title: string;
    crumbs: { label: string; url: string | null }[];
    action: string;
    csrfToken: string;
    changePasswordUrl: string;
    avatar: string;
    countries: string[];
    profile: ProfileFields;
}

const SOCIALS: { name: keyof ProfileFields; label: string }[] = [
    { name: 'facebook_url', label: 'Facebook' },
    { name: 'google_url', label: 'Google' },
    { name: 'twitter_url', label: 'Twitter' },
    { name: 'instagram_url', label: 'Instagram' },
    { name: 'linkedin_url', label: 'Linkedin' },
    { name: 'xing_url', label: 'Xing' },
];

const fieldClass =
    'w-full rounded-sm border border-[var(--border-strong)] bg-[var(--surface)] px-3 py-2.5 text-base text-[var(--text)] placeholder:text-[var(--text-subtle)] focus:border-[var(--ring)] focus:outline-none focus:ring-2 focus:ring-[var(--ring)]/30';

/**
 * Self-service profile edit. A native multipart form PUTs to update_user_info
 * (method-spoofed) so the avatar file uploads naturally; validation errors and
 * the success flash arrive via Inertia shared props. Noindex — SEO head Blade.
 */
export default function ProfileEdit({ shell, title, crumbs, action, csrfToken, changePasswordUrl, avatar, countries, profile }: ProfileEditProps) {
    const { t } = useTranslation();
    const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
    const { errors, flash } = usePage<SharedProps>().props;
    const errorList = Object.values(errors ?? {});
    const [preview, setPreview] = useState(avatar);

    return (
        <PublicLayout shell={shell}>
            <header className="border-b border-[var(--border)] bg-[var(--surface-2)]">
                <div className="mx-auto max-w-[720px] px-5 py-12 sm:px-8 sm:py-16">
                    <Crumbs crumbs={crumbs} />
                    <h1 className="font-serif text-[clamp(2.25rem,4vw,3.052rem)] font-medium leading-[1.08] tracking-[-0.01em] text-[var(--text)]">{title}</h1>
                </div>
            </header>

            <section className="mx-auto max-w-[720px] px-5 py-16 sm:px-8 sm:py-20">
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

                <form action={action} method="post" encType="multipart/form-data" className="space-y-8" noValidate>
                    <input type="hidden" name="_method" value="PUT" />
                    <input type="hidden" name="_token" value={csrfToken} />

                    <div className="flex items-center gap-6">
                        <img src={preview} alt={profile.username ?? ''} width={96} height={96} className="h-24 w-24 rounded-2xl object-cover ring-1 ring-[var(--border-strong)]" />
                        <label className="inline-flex cursor-pointer items-center gap-2 rounded-md border border-[var(--border-strong)] bg-[var(--surface)] px-4 py-2.5 text-sm font-medium text-[var(--text)] transition-colors hover:bg-[var(--surface-2)]">
                            {tr('default/profile.edit', 'Edit')}
                            <input
                                type="file"
                                name="avatar"
                                accept="image/*"
                                className="sr-only"
                                onChange={(e) => {
                                    const file = e.target.files?.[0];
                                    if (file) setPreview(URL.createObjectURL(file));
                                }}
                            />
                        </label>
                    </div>

                    {profile.username && (
                        <div>
                            <span className="mb-1.5 block text-sm font-medium text-[var(--text)]">{tr('default/profile.username', 'Username')}</span>
                            <p className="flex h-11 select-none items-center rounded-sm border border-[var(--border)] bg-[var(--surface-2)] px-3 text-base text-[var(--text-muted)]">{profile.username}</p>
                        </div>
                    )}

                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field id="email" label={tr('default/profile.email', 'Email')}>
                            <input id="email" type="email" name="email" defaultValue={profile.email ?? ''} className={fieldClass} />
                        </Field>
                        <Field id="city" label={tr('default/profile.city', 'City')}>
                            <input id="city" type="text" name="city" defaultValue={profile.city ?? ''} className={fieldClass} />
                        </Field>
                        <Field id="name" label={tr('default/profile.name', 'Name')}>
                            <input id="name" type="text" name="name" defaultValue={profile.name ?? ''} className={fieldClass} />
                        </Field>
                        <Field id="surname" label={tr('default/profile.surname', 'Surname')}>
                            <input id="surname" type="text" name="surname" defaultValue={profile.surname ?? ''} className={fieldClass} />
                        </Field>
                        <Field id="country" label={tr('default/profile.country', 'Country')}>
                            <select id="country" name="country" defaultValue={profile.country ?? ''} className={fieldClass}>
                                {countries.map((c) => (
                                    <option key={c} value={c}>
                                        {c}
                                    </option>
                                ))}
                            </select>
                        </Field>
                    </div>

                    <Field id="about_me" label={tr('default/profile.about', 'About')}>
                        <textarea id="about_me" name="about_me" rows={4} defaultValue={profile.about_me ?? ''} className={`${fieldClass} resize-y`} />
                    </Field>

                    <fieldset>
                        <legend className="mb-2 text-sm font-medium text-[var(--text)]">{tr('default/profile.gender', 'Gender')}</legend>
                        <div className="flex flex-wrap gap-3">
                            {(['male', 'female'] as const).map((g) => (
                                <label key={g} className="flex min-w-[140px] flex-1 cursor-pointer items-center gap-3 rounded-lg border border-[var(--border-strong)] px-4 py-3 transition-colors has-[:checked]:border-[var(--primary)] has-[:checked]:bg-[var(--surface-2)]">
                                    <input type="radio" name="gender" value={g} defaultChecked={profile.gender === g} className="h-4 w-4" />
                                    <span className="text-sm font-medium text-[var(--text)]">{tr(`default/profile.gender_${g}`, g === 'male' ? 'Male' : 'Female')}</span>
                                </label>
                            ))}
                        </div>
                    </fieldset>

                    <div className="grid gap-5 sm:grid-cols-2">
                        {SOCIALS.map((s) => (
                            <Field key={s.name} id={s.name} label={s.label}>
                                <input id={s.name} type="text" name={s.name} placeholder="https://" defaultValue={(profile[s.name] as string | null) ?? ''} className={fieldClass} />
                            </Field>
                        ))}
                    </div>

                    <div className="flex items-center justify-between">
                        <a href={changePasswordUrl} className="text-sm font-medium text-[var(--primary)] transition-colors hover:opacity-80">
                            {tr('default/profile.change_password', 'Change password')}
                        </a>
                        <button type="submit" className="inline-flex items-center gap-2 rounded-md bg-[var(--primary)] px-5 py-2.5 text-sm font-medium text-[var(--primary-contrast)] transition hover:opacity-90">
                            {tr('default/profile.updated_profile', 'Update profile')}
                        </button>
                    </div>
                </form>
            </section>
        </PublicLayout>
    );
}

function Crumbs({ crumbs }: { crumbs: { label: string; url: string | null }[] }) {
    return (
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

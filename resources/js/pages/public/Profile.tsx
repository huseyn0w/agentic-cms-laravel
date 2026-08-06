import { useTranslation } from 'react-i18next';
import { PublicLayout } from '@/layouts/PublicLayout';
import type { Shell } from '@/layouts/PublicLayout';

interface ProfileProps {
    shell: Shell;
    profile: {
        displayName: string;
        username: string;
        avatar: string;
        role: string | null;
        gender: string | null;
        aboutMe: string | null;
        email: string;
        country: string | null;
        city: string | null;
        socials: { label: string; url: string }[];
        isOwnProfile: boolean;
        editUrl: string;
    };
}

/**
 * Public user profile. SEO head (ProfilePage + Person JSON-LD) is
 * server-rendered by Blade (seo-meta via the $user viewData) — no <Head> here.
 */
export default function Profile({ shell, profile }: ProfileProps) {
    const { t } = useTranslation();
    const tr = (k: string, f: string) => (t(k) === k ? f : t(k));

    return (
        <PublicLayout shell={shell}>
            <section className="mx-auto max-w-[1080px] px-5 py-16 sm:px-8 sm:py-24">
                <div className="grid gap-8 lg:grid-cols-[280px_1fr]">
                    <div className="flex flex-col items-center gap-5 lg:items-start">
                        <img src={profile.avatar} alt={profile.displayName} width={96} height={96} className="h-24 w-24 shrink-0 rounded-2xl object-cover shadow-card ring-1 ring-[var(--border-strong)]" />
                        <div className="text-center lg:text-left">
                            <h1 className="text-2xl font-medium tracking-[-0.01em] text-[var(--text)]">{profile.displayName}</h1>
                            <p className="mt-1 font-mono text-xs uppercase tracking-[0.08em] text-[var(--text-muted)]">@{profile.username}</p>
                        </div>
                        <div className="flex flex-wrap items-center justify-center gap-2 lg:justify-start">
                            {profile.role && <span className="rounded-full bg-[var(--primary)] px-3 py-1 text-xs font-medium text-[var(--primary-contrast)]">{profile.role}</span>}
                            {profile.gender && <span className="rounded-full border border-[var(--border)] px-3 py-1 text-xs capitalize text-[var(--text-muted)]">{profile.gender}</span>}
                        </div>
                        {profile.isOwnProfile && (
                            <a href={profile.editUrl} className="inline-flex items-center gap-2 rounded-md border border-[var(--border)] px-4 py-2 text-sm text-[var(--text)] transition hover:bg-[var(--surface-2)]">
                                {tr('default/profile.edit', 'Edit profile')}
                            </a>
                        )}
                    </div>

                    <div className="flex flex-col gap-8">
                        {profile.aboutMe && (
                            <div className="rounded-lg border border-[var(--border)] bg-[var(--surface)] p-6">
                                <p className="mb-3 font-mono text-xs uppercase tracking-[0.08em] text-[var(--text-muted)]">{tr('default/profile.about_me', 'About me')}</p>
                                <p className="border-l-2 border-[var(--primary)] pl-4 text-lg leading-relaxed text-[var(--text)]">{profile.aboutMe}</p>
                            </div>
                        )}

                        <div className="rounded-lg border border-[var(--border)] bg-[var(--surface)] p-6">
                            <p className="mb-4 font-mono text-xs uppercase tracking-[0.08em] text-[var(--text-muted)]">{tr('default/profile.details', 'Details')}</p>
                            <dl className="grid gap-x-10 gap-y-5 sm:grid-cols-2">
                                <div>
                                    <dt className="font-mono text-xs uppercase tracking-[0.08em] text-[var(--text-muted)]">{tr('default/profile.email', 'Email')}</dt>
                                    <dd className="mt-1 text-base text-[var(--text)]">{profile.email}</dd>
                                </div>
                                {profile.country && (
                                    <div>
                                        <dt className="font-mono text-xs uppercase tracking-[0.08em] text-[var(--text-muted)]">{tr('default/profile.country', 'Country')}</dt>
                                        <dd className="mt-1 text-base text-[var(--text)]">{profile.country}</dd>
                                    </div>
                                )}
                                {profile.city && (
                                    <div>
                                        <dt className="font-mono text-xs uppercase tracking-[0.08em] text-[var(--text-muted)]">{tr('default/profile.city', 'City')}</dt>
                                        <dd className="mt-1 text-base text-[var(--text)]">{profile.city}</dd>
                                    </div>
                                )}
                            </dl>
                        </div>

                        {profile.socials.length > 0 && (
                            <div className="rounded-lg border border-[var(--border)] bg-[var(--surface)] p-6">
                                <p className="mb-4 font-mono text-xs uppercase tracking-[0.08em] text-[var(--text-muted)]">Links</p>
                                <div className="flex flex-wrap gap-2">
                                    {profile.socials.map((social) => (
                                        <a
                                            key={social.label}
                                            href={social.url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex items-center gap-2 rounded-full border border-[var(--border-strong)] bg-[var(--surface)] px-4 py-2 text-sm font-medium text-[var(--text)] transition-colors hover:border-[var(--primary)] hover:bg-[var(--surface-2)]"
                                        >
                                            {social.label} ↗
                                        </a>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

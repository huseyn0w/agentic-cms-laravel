import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useEffect, useState } from 'react';
import { NewsletterSubscribe } from '@/components/public/NewsletterSubscribe';

const THEME_KEY = 'agentic-cms-theme';

export interface MenuItem {
    title: string;
    url: string;
    /** true = our own migrated Inertia page → prefetching client nav; false = full load. */
    internal: boolean;
    children: MenuItem[];
}

/**
 * A nav link that navigates instantly (Inertia, hover-prefetched) when the
 * target is one of our migrated pages, and does an ordinary full-page load
 * otherwise — custom/external links or pages still on Blade.
 */
function NavLink({ item, className }: { item: MenuItem; className: string }) {
    if (item.internal) {
        return (
            <Link href={item.url} prefetch="hover" cacheFor="30s" className={className}>
                {item.title}
            </Link>
        );
    }
    return (
        <a href={item.url} className={className}>
            {item.title}
        </a>
    );
}

export interface LanguageLink {
    code: string;
    url: string;
    title: string;
    icon: string | null;
    current: boolean;
}

export interface Shell {
    wordmark: string;
    homeUrl: string;
    logoUrl: string | null;
    searchUrl: string;
    currentLang: string;
    menu: MenuItem[];
    languages: LanguageLink[];
    general: { websiteName: string; membership: boolean; bookingUrl?: string | null };
    site: { copyright: string | null; linkedinUrl: string | null; githubUrl: string | null };
    legalLinks?: { title: string; url: string }[];
    auth: {
        user: { name: string } | null;
        canSeeAdmin: boolean;
        loginUrl?: string;
        registerUrl?: string;
        profileUrl?: string;
        adminUrl?: string;
        logoutUrl?: string;
    };
}

/** The gradient wordmark: a small gradient tile + the site name in Geist. */
function Wordmark({ shell }: { shell: Shell }) {
    const [logoFailed, setLogoFailed] = useState(false);
    if (shell.logoUrl && !logoFailed) {
        return <img src={shell.logoUrl} alt="" height={32} className="h-8 w-auto" onError={() => setLogoFailed(true)} />;
    }
    return (
        <span className="flex items-center gap-2.5">
            <span
                className="h-7 w-7 shrink-0 rounded-[8px] shadow-[0_1px_2px_rgba(9,9,11,0.14)]"
                style={{ backgroundImage: 'var(--grad)' }}
                aria-hidden="true"
            />
            <span className="text-[17px] font-semibold tracking-[-0.02em] text-[var(--text)]">{shell.wordmark}</span>
        </span>
    );
}

/**
 * The public site shell: a translucent sticky header and a footer, rendered
 * from shell props by PublicShell (PHP). Geist typography with a blue→violet→
 * pink accent gradient; light/dark toggled by the button in the header.
 */
export function PublicLayout({ shell, children }: { shell: Shell; children: ReactNode }) {
    const [mobileOpen, setMobileOpen] = useState(false);
    const [isDark, setIsDark] = useState(false);

    useEffect(() => {
        const stored = localStorage.getItem(THEME_KEY);
        const dark = stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
        document.documentElement.classList.toggle('dark', dark);
        setIsDark(dark);
    }, []);

    const toggleDark = () => {
        const next = !isDark;
        setIsDark(next);
        document.documentElement.classList.toggle('dark', next);
        try {
            localStorage.setItem(THEME_KEY, next ? 'dark' : 'light');
        } catch {
            /* storage unavailable — the class toggle still applies for this view */
        }
    };

    const { auth } = shell;

    return (
        <div className="theme-default min-h-[100dvh] bg-[var(--bg)] text-[var(--text)] antialiased">
            <a
                href="#main"
                data-testid="skip-link"
                className="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-md focus:bg-[var(--surface)] focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-[var(--text)] focus:shadow-lg focus:outline-none focus:ring-2 focus:ring-[var(--ring)] focus:ring-offset-2"
            >
                Skip to content
            </a>

            <header
                className="sticky top-0 z-40 border-b border-[var(--border)] bg-[var(--bg)]/80 backdrop-blur-md"
                data-testid="public-header"
            >
                <div className="mx-auto flex h-16 max-w-[76rem] items-center justify-between gap-4 px-5 sm:px-8">
                    <a
                        href={shell.homeUrl}
                        className="flex shrink-0 items-center"
                        aria-label={shell.general.websiteName}
                        data-testid="header-wordmark"
                    >
                        <Wordmark shell={shell} />
                    </a>

                    <nav
                        className="hidden flex-1 items-center justify-center gap-1 lg:flex"
                        aria-label="Primary"
                        data-testid="primary-nav"
                    >
                        {shell.menu.map((item) => (
                            <NavLink
                                key={item.title + item.url}
                                item={item}
                                className="rounded-md px-3 py-2 text-[14px] text-[var(--text-subtle)] transition-colors hover:bg-[var(--surface-2)] hover:text-[var(--text)]"
                            />
                        ))}
                    </nav>

                    <div className="hidden items-center gap-1 lg:flex">
                        <a
                            href={shell.searchUrl}
                            className="inline-flex h-9 w-9 items-center justify-center rounded-md text-[var(--text-subtle)] transition-colors hover:bg-[var(--surface-2)] hover:text-[var(--text)]"
                            aria-label="Search"
                            data-testid="header-search"
                        >
                            <SearchIcon />
                        </a>

                        {shell.languages.length > 0 && (
                            <div className="flex items-center gap-0.5" data-testid="locale-switcher">
                                {shell.languages.map((lang) => {
                                    const className =
                                        'rounded-md px-2 py-1 font-mono text-xs uppercase tracking-[0.06em] transition-colors ' +
                                        (lang.current
                                            ? 'text-[var(--accent)]'
                                            : 'text-[var(--text-subtle)] hover:bg-[var(--surface-2)] hover:text-[var(--text)]');

                                    // The current locale has no target URL — render a plain span.
                                    // Other locales are real Inertia pages now (URL-driven locale),
                                    // so prefetch them on hover for instant switching.
                                    if (lang.current) {
                                        return (
                                            <span
                                                key={lang.code}
                                                data-testid={`lang-${lang.code.toLowerCase()}`}
                                                aria-current="true"
                                                className={className}
                                            >
                                                {lang.code}
                                            </span>
                                        );
                                    }

                                    return (
                                        <Link
                                            key={lang.code}
                                            href={lang.url}
                                            prefetch="hover"
                                            data-testid={`lang-${lang.code.toLowerCase()}`}
                                            className={className}
                                        >
                                            {lang.code}
                                        </Link>
                                    );
                                })}
                            </div>
                        )}

                        <button
                            type="button"
                            onClick={toggleDark}
                            aria-pressed={isDark}
                            aria-label="Toggle dark mode"
                            className="inline-flex h-9 w-9 items-center justify-center rounded-md text-[var(--text-subtle)] transition-colors hover:bg-[var(--surface-2)] hover:text-[var(--text)]"
                            data-testid="dark-toggle"
                        >
                            {isDark ? <SunIcon /> : <MoonIcon />}
                        </button>

                        {shell.general.bookingUrl && (
                            <a
                                href={shell.general.bookingUrl}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="btn-accent ml-1 h-9 px-4 text-[14px] font-medium"
                                data-testid="header-booking"
                            >
                                Book a call
                            </a>
                        )}

                        <div className="ml-1 h-5 w-px bg-[var(--border)]" aria-hidden="true" />

                        {auth.user ? (
                            <>
                                {auth.canSeeAdmin && auth.adminUrl && (
                                    <a href={auth.adminUrl} className="rounded-md px-3 py-2 text-[14px] text-[var(--text-subtle)] transition-colors hover:text-[var(--text)]">
                                        Admin
                                    </a>
                                )}
                                {auth.logoutUrl && (
                                    <Link href={auth.logoutUrl} method="post" as="button" className="ml-1 rounded-md px-3 py-2 text-[14px] text-[var(--text-subtle)] transition-colors hover:text-[var(--text)]">
                                        Log out
                                    </Link>
                                )}
                            </>
                        ) : (
                            <>
                                {auth.loginUrl && (
                                    <a href={auth.loginUrl} className="rounded-md px-3 py-2 text-[14px] text-[var(--text-subtle)] transition-colors hover:text-[var(--text)]">
                                        Log in
                                    </a>
                                )}
                                {shell.general.membership && auth.registerUrl && (
                                    <a
                                        href={auth.registerUrl}
                                        className="btn-accent ml-1 h-9 px-4 text-[14px] font-medium"
                                    >
                                        Register
                                    </a>
                                )}
                            </>
                        )}
                    </div>

                    <button
                        type="button"
                        onClick={() => setMobileOpen((o) => !o)}
                        aria-expanded={mobileOpen}
                        aria-controls="mobile-drawer"
                        aria-label="Toggle navigation"
                        className="inline-flex h-10 w-10 items-center justify-center rounded-full border border-[var(--border)] text-[var(--text-subtle)] transition active:scale-95 lg:hidden"
                        data-testid="mobile-menu-button"
                    >
                        {mobileOpen ? <CloseIcon /> : <MenuIcon />}
                    </button>
                </div>

                {mobileOpen && (
                    <div
                        id="mobile-drawer"
                        className="border-t border-[var(--border)] bg-[var(--bg)] px-5 pb-6 pt-2 sm:px-8 lg:hidden"
                        role="dialog"
                        aria-label="Navigation menu"
                        aria-modal="true"
                    >
                        <nav className="flex flex-col gap-0.5 py-2" aria-label="Mobile primary" data-testid="mobile-nav">
                            {shell.menu.map((item) => (
                                <NavLink
                                    key={item.title + item.url}
                                    item={item}
                                    className="rounded-md px-2 py-2 text-sm text-[var(--text-subtle)] transition-colors hover:bg-[var(--surface-2)] hover:text-[var(--text)]"
                                />
                            ))}
                            <a href={shell.searchUrl} className="rounded-md px-2 py-2 text-sm text-[var(--text-subtle)] transition-colors hover:text-[var(--text)]">
                                Search
                            </a>
                            {shell.general.bookingUrl && (
                                <a
                                    href={shell.general.bookingUrl}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="btn-accent mt-2 h-10 text-sm font-medium"
                                    data-testid="mobile-booking"
                                >
                                    Book a call
                                </a>
                            )}
                            {!auth.user && shell.general.membership && auth.registerUrl && (
                                <a href={auth.registerUrl} className="btn-accent mt-2 h-10 text-sm font-medium">
                                    Register
                                </a>
                            )}
                        </nav>
                    </div>
                )}
            </header>

            <main id="main">{children}</main>

            <footer className="mt-24 border-t border-[var(--border)] bg-[var(--surface-2)]" data-testid="public-footer">
                <div className="mx-auto max-w-[76rem] px-5 py-14 sm:px-8">
                    <div className="mb-10 border-b border-[var(--border)] pb-10">
                        <NewsletterSubscribe />
                    </div>
                    <div className="flex flex-col gap-8 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <a href={shell.homeUrl} className="inline-flex" data-testid="footer-wordmark">
                                <Wordmark shell={shell} />
                            </a>
                            {shell.site.copyright && (
                                <div
                                    className="mt-4 max-w-md text-sm leading-relaxed text-[var(--text-subtle)]"
                                    dangerouslySetInnerHTML={{ __html: shell.site.copyright }}
                                />
                            )}
                        </div>

                        {(shell.site.linkedinUrl || shell.site.githubUrl) && (
                            <div className="flex items-center gap-2">
                                {shell.site.linkedinUrl && (
                                    <a
                                        href={shell.site.linkedinUrl}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label="LinkedIn"
                                        className="inline-flex h-9 w-9 items-center justify-center rounded-full border border-[var(--border)] text-[var(--text-subtle)] transition hover:border-[var(--border-strong)] hover:text-[var(--text)]"
                                    >
                                        <LinkedInIcon />
                                    </a>
                                )}
                                {shell.site.githubUrl && (
                                    <a
                                        href={shell.site.githubUrl}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label="GitHub"
                                        className="inline-flex h-9 w-9 items-center justify-center rounded-full border border-[var(--border)] text-[var(--text-subtle)] transition hover:border-[var(--border-strong)] hover:text-[var(--text)]"
                                    >
                                        <GitHubIcon />
                                    </a>
                                )}
                            </div>
                        )}
                    </div>

                    {shell.legalLinks && shell.legalLinks.length > 0 && (
                        <nav className="mt-10 flex flex-wrap gap-x-6 gap-y-2 border-t border-[var(--border)] pt-6" data-testid="footer-legal" aria-label="Legal">
                            {shell.legalLinks.map((link) => (
                                <a
                                    key={link.url}
                                    href={link.url}
                                    className="text-[13px] text-[var(--text-subtle)] transition-colors hover:text-[var(--text)]"
                                >
                                    {link.title}
                                </a>
                            ))}
                        </nav>
                    )}
                </div>
            </footer>
        </div>
    );
}

/* --- inline icons --- */
const iconProps = { width: 18, height: 18, viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: 2, 'aria-hidden': true } as const;

const SearchIcon = () => (
    <svg {...iconProps}>
        <circle cx="11" cy="11" r="8" />
        <path d="m21 21-4.3-4.3" />
    </svg>
);
const SunIcon = () => (
    <svg {...iconProps}>
        <circle cx="12" cy="12" r="4" />
        <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
    </svg>
);
const MoonIcon = () => (
    <svg {...iconProps}>
        <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z" />
    </svg>
);
const MenuIcon = () => (
    <svg {...iconProps} width={20} height={20}>
        <path d="M3 6h18M3 12h18M3 18h18" />
    </svg>
);
const CloseIcon = () => (
    <svg {...iconProps} width={20} height={20}>
        <path d="M18 6 6 18M6 6l12 12" />
    </svg>
);
const LinkedInIcon = () => (
    <svg width={16} height={16} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M4.98 3.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM3 9h4v12H3zM10 9h3.8v1.7h.05c.53-1 1.83-2.05 3.77-2.05C21.4 8.65 22 11 22 14.1V21h-4v-6.1c0-1.45-.03-3.3-2-3.3s-2.3 1.57-2.3 3.2V21h-4z" />
    </svg>
);
const GitHubIcon = () => (
    <svg width={16} height={16} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M12 2C6.48 2 2 6.58 2 12.25c0 4.53 2.87 8.37 6.84 9.73.5.09.68-.22.68-.49v-1.7c-2.78.62-3.37-1.37-3.37-1.37-.46-1.18-1.11-1.5-1.11-1.5-.9-.64.07-.62.07-.62 1 .07 1.53 1.05 1.53 1.05.89 1.56 2.34 1.11 2.91.85.09-.66.35-1.11.63-1.36-2.22-.26-4.56-1.14-4.56-5.07 0-1.12.39-2.03 1.03-2.75-.1-.26-.45-1.3.1-2.7 0 0 .84-.28 2.75 1.05a9.3 9.3 0 0 1 5 0c1.91-1.33 2.75-1.05 2.75-1.05.55 1.4.2 2.44.1 2.7.64.72 1.03 1.63 1.03 2.75 0 3.94-2.34 4.81-4.57 5.06.36.32.68.94.68 1.9v2.82c0 .27.18.59.69.49A10.26 10.26 0 0 0 22 12.25C22 6.58 17.52 2 12 2z" />
    </svg>
);

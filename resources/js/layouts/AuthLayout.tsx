import type { ReactNode } from 'react';
import { useEffect } from 'react';

const THEME_KEY = 'agentic-cms-theme';

function applyStoredTheme(): void {
    const stored = localStorage.getItem(THEME_KEY);
    const dark = stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
    document.documentElement.classList.toggle('dark', dark);
}

interface AuthLayoutProps {
    title: string;
    subtitle?: string;
    children: ReactNode;
}

export function AuthLayout({ title, subtitle, children }: AuthLayoutProps) {
    // Honor the user's saved theme; the Inertia root does not load front.js.
    useEffect(() => {
        applyStoredTheme();
    }, []);

    return (
        <div className="theme-default min-h-screen bg-bg text-fg lg:grid lg:grid-cols-[45fr_55fr]">
            {/* Brand panel — the signature blue→violet→pink gradient. */}
            <aside
                className="relative hidden overflow-hidden text-white lg:flex lg:flex-col lg:justify-between lg:p-12"
                style={{ backgroundImage: 'var(--grad)' }}
            >
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-0 opacity-[0.12]"
                    style={{
                        backgroundImage: 'radial-gradient(currentColor 1px, transparent 1px)',
                        backgroundSize: '22px 22px',
                    }}
                />
                <span className="relative flex items-center gap-2.5 text-[19px] font-semibold tracking-[-0.02em]">
                    <span className="h-7 w-7 rounded-[8px] bg-white/25 ring-1 ring-white/40" aria-hidden="true" />
                    Agentic CMS
                </span>
                <p className="relative max-w-sm text-[28px] font-semibold leading-[1.15] tracking-[-0.02em]">
                    AI First CMS you run from your AI assistant
                </p>
                <span className="relative text-sm opacity-80">&copy; Agentic CMS</span>
            </aside>

            {/* Form panel */}
            <main className="flex min-h-screen items-center justify-center px-5 py-16 lg:py-20">
                <div className="w-full max-w-[440px]">
                    <div className="mb-8 text-center lg:text-left">
                        <h1 className="text-3xl font-semibold tracking-[-0.02em] text-fg">{title}</h1>
                        {subtitle && <p className="mt-2 text-sm text-muted">{subtitle}</p>}
                    </div>
                    {children}
                </div>
            </main>
        </div>
    );
}

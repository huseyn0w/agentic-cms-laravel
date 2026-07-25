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
            {/* Brand panel */}
            <aside className="relative hidden overflow-hidden bg-primary text-primary-contrast lg:flex lg:flex-col lg:justify-between lg:p-12">
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-0 opacity-[0.08]"
                    style={{
                        backgroundImage: 'radial-gradient(currentColor 1px, transparent 1px)',
                        backgroundSize: '22px 22px',
                    }}
                />
                <span className="relative font-serif text-2xl font-semibold tracking-tight">Agentic CMS</span>
                <p className="relative max-w-sm font-serif text-3xl leading-tight">
                    AI First CMS you run from your AI assistant
                </p>
                <span className="relative text-sm opacity-70">&copy; Agentic CMS</span>
            </aside>

            {/* Form panel */}
            <main className="flex min-h-screen items-center justify-center px-5 py-16 lg:py-20">
                <div className="w-full max-w-[440px]">
                    <div className="mb-8 text-center lg:text-left">
                        <h1 className="font-serif text-3xl font-semibold tracking-tight text-fg">{title}</h1>
                        {subtitle && <p className="mt-2 text-sm text-muted">{subtitle}</p>}
                    </div>
                    {children}
                </div>
            </main>
        </div>
    );
}

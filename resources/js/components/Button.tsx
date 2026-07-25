import type { ButtonHTMLAttributes, ReactNode } from 'react';

type Variant = 'primary' | 'secondary' | 'outline' | 'ghost' | 'destructive';
type Size = 'sm' | 'md' | 'lg';

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: Variant;
    size?: Size;
    href?: string;
    loading?: boolean;
    children: ReactNode;
}

const VARIANT: Record<Variant, string> = {
    primary: 'bg-primary text-primary-contrast hover:bg-primary-hover',
    secondary: 'bg-surface-2 text-fg hover:bg-border',
    outline: 'border border-strong bg-transparent text-fg hover:bg-surface-2',
    ghost: 'bg-transparent text-fg hover:bg-surface-2',
    destructive: 'bg-error text-primary-contrast hover:opacity-90',
};

const SIZE: Record<Size, string> = {
    sm: 'h-8 px-3 text-sm',
    md: 'h-10 px-4 text-base',
    lg: 'h-12 px-6 text-lg',
};

const BASE =
    'inline-flex items-center justify-center gap-2 font-sans font-medium rounded-md transition-colors duration-[var(--dur-fast)] ease-[var(--ease-out)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 active:scale-[0.98] motion-reduce:active:scale-100 disabled:cursor-not-allowed disabled:opacity-50 disabled:pointer-events-none';

export function Button({
    variant = 'primary',
    size = 'md',
    href,
    loading = false,
    children,
    className = '',
    type = 'button',
    ...rest
}: ButtonProps) {
    const classes = `${BASE} ${VARIANT[variant]} ${SIZE[size]} ${className}`.trim();

    if (href) {
        // eslint-disable-next-line jsx-a11y/anchor-has-content
        return (
            <a href={href} className={classes} {...(rest as Record<string, unknown>)}>
                {children}
            </a>
        );
    }

    return (
        <button type={type} className={classes} aria-busy={loading || undefined} {...rest}>
            {loading && (
                <svg className="animate-spin shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
            )}
            {children}
        </button>
    );
}

import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './app/**/*.php',
        './bootstrap/agentic-cms-laravel-helpers.php',
    ],

    /*
     |--------------------------------------------------------------------------
     | Dark mode — toggled via the `.dark` class on <html>.
     |--------------------------------------------------------------------------
     | Tokens flip in `.dark { --bg: … }` (tokens.css). The class is applied
     | by JS (front.js / admin.js) from localStorage / prefers-color-scheme.
     */
    darkMode: 'class',

    /*
     |--------------------------------------------------------------------------
     | Preflight stays disabled GLOBALLY for this phase.
     |--------------------------------------------------------------------------
     | Tailwind is loaded ALONGSIDE the legacy Bootstrap 4 admin bundle. A
     | global Preflight reset would clobber Bootstrap typography/forms on the
     | still-Bootstrap admin (resources/views/cpanel/**). Instead, the public
     | theme ships its own SCOPED reset + base layer under the `.theme-default`
     | wrapper class (see resources/css/app.css). The admin never receives that
     | class, so it stays visually unaffected.
     */
    corePlugins: {
        preflight: false,
    },

    theme: {
        // Editorial type scale — magazine rhythm, generous body line-height.
        fontSize: {
            xs: ['0.75rem', { lineHeight: '1.5' }],
            sm: ['0.875rem', { lineHeight: '1.6' }],
            base: ['1.0625rem', { lineHeight: '1.7' }],
            lg: ['1.1875rem', { lineHeight: '1.7' }],
            xl: ['1.375rem', { lineHeight: '1.45' }],
            '2xl': ['1.75rem', { lineHeight: '1.3' }],
            '3xl': ['2.1875rem', { lineHeight: '1.18' }],
            '4xl': ['2.75rem', { lineHeight: '1.1' }],
            '5xl': ['3.5rem', { lineHeight: '1.04' }],
            '6xl': ['4.5rem', { lineHeight: '1.0' }],
        },
        extend: {
            colors: {
                // ── §2 semantic token bridge (CSS custom property → Tailwind) ──
                // These map utilities like `bg-bg`, `bg-surface`, `text-muted`,
                // `border-border`, `bg-primary`, `text-primary`, etc. to the vars
                // defined in tokens.css. Dark mode flips automatically via `.dark`.
                bg: 'var(--bg)',
                'surface-2': 'var(--surface-2)',
                // Foreground text tokens as FLAT keys to avoid the `text-text-*`
                // stutter: use `text-fg` (primary), `text-muted`, `text-subtle`.
                // Primary body text is also inherited from the .theme-* base.
                fg: 'var(--text)',
                muted: 'var(--text-muted)',
                subtle: 'var(--text-subtle)',
                primary: { DEFAULT: 'var(--primary)', hover: 'var(--primary-hover)', contrast: 'var(--primary-contrast)' },
                accent: 'var(--accent)',
                border: { DEFAULT: 'var(--border)', strong: 'var(--border-strong)' },
                ring: 'var(--ring)',
                error: { DEFAULT: 'var(--error)', bg: 'var(--error-bg)' },

                // Single committed accent: a deep editorial garnet. Used for
                // links, active state, and the "like" action — locked sitewide.
                brand: {
                    50: '#fbf3f2',
                    100: '#f7e3e1',
                    200: '#eec5c1',
                    300: '#e29d97',
                    400: '#d16b63',
                    500: '#bf463c',
                    600: '#b0322b',
                    700: '#932520',
                    800: '#7a201d',
                    900: '#661f1d',
                    950: '#380c0a',
                },
                // Near-neutral ink ramp (chroma kept tiny — paper + ink, not
                // the cream/beige AI default).
                ink: {
                    50: '#f6f6f4',
                    100: '#eceae6',
                    200: '#d9d6cf',
                    300: '#bdb9b0',
                    400: '#94908a',
                    500: '#6f6c66',
                    600: '#565350',
                    700: '#403e3c',
                    800: '#2a2927',
                    900: '#1a1a18',
                    950: '#111110',
                },
                paper: '#fbfbf9',
                // `surface` — legacy hardcoded + new semantic token override.
                surface: { DEFAULT: 'var(--surface)', 2: 'var(--surface-2)' },
                // Admin semantic state ramps — kept intact for back-compat
                // (admin.css uses success-50/100/600/700 etc.). The semantic
                // DEFAULT and bg sub-keys add the token-bridged utilities on top.
                success: {
                    DEFAULT: 'var(--success)',
                    bg: 'var(--success-bg)',
                    50: '#f0f7f1',
                    100: '#d9ecdc',
                    500: '#3f8f54',
                    600: '#317444',
                    700: '#285d38',
                },
                warning: {
                    DEFAULT: 'var(--warning)',
                    bg: 'var(--warning-bg)',
                    50: '#fbf4e8',
                    100: '#f6e6c8',
                    500: '#c08a2b',
                    600: '#a3731f',
                    700: '#7f5917',
                },
                danger: {
                    50: '#fcf1f0',
                    100: '#f7dcd9',
                    500: '#c5453a',
                    600: '#aa362c',
                    700: '#8a2a22',
                },
                info: {
                    50: '#eef4f8',
                    100: '#d3e3ee',
                    500: '#3a7da3',
                    600: '#2f6585',
                    700: '#264f68',
                },
            },
            fontFamily: {
                // Self-hosted variable fonts (DESIGN_SYSTEM §3/§7).
                // Family names confirmed from @fontsource-variable package CSS:
                //   inter/index.css → font-family: 'Inter Variable'
                //   newsreader/index.css → font-family: 'Newsreader Variable'
                //   geist-mono/index.css → font-family: 'Geist Mono Variable'
                serif: ['"Newsreader Variable"', ...defaultTheme.fontFamily.serif],
                sans: ['"Inter Variable"', ...defaultTheme.fontFamily.sans],
                mono: ['"Geist Mono Variable"', ...defaultTheme.fontFamily.mono],
            },
            letterSpacing: {
                tightest: '-0.04em',
            },
            maxWidth: {
                prose: '68ch',
            },
            borderRadius: {
                // §4 token-bridged radius scale (via CSS custom properties).
                sm: 'var(--radius-sm)',
                md: 'var(--radius-md)',
                lg: 'var(--radius-lg)',
                xl: 'var(--radius-xl)',
                full: 'var(--radius-full)',
                // Legacy hardcoded values kept under distinct keys so any
                // `rounded-2xl` usage continues to resolve without drift.
                '2xl': '1.25rem',
            },
            boxShadow: {
                // Shadows tinted toward the warm ink hue, never pure black.
                card: '0 1px 2px rgba(26,26,24,0.04), 0 8px 24px -12px rgba(26,26,24,0.12)',
                lift: '0 2px 4px rgba(26,26,24,0.05), 0 18px 40px -18px rgba(26,26,24,0.20)',
            },
            transitionTimingFunction: {
                // §6 motion token bridge (CSS custom properties).
                'out-expo': 'var(--ease-out)',
                'in-out-expo': 'var(--ease-in-out)',
            },
            zIndex: {
                // Semantic layer scale for the admin shell.
                dropdown: '40',
                sticky: '30',
                sidebar: '50',
                backdrop: '60',
                modal: '70',
                toast: '80',
            },
            keyframes: {
                'fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(12px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
            },
            animation: {
                'fade-up': 'fade-up 0.6s cubic-bezier(0.16, 1, 0.3, 1) both',
            },
        },
    },

    plugins: [forms, typography],
};

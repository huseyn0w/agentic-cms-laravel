import type { ReactNode } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import type { SharedProps } from '@/lib/types';

const ADMIN = '/agentic-cms-laravel-admin';

export function Topbar({ breadcrumb }: { breadcrumb?: ReactNode }) {
  const { auth, locale } = usePage<SharedProps>().props;
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const initials = (auth.user?.name ?? '?').slice(0, 2).toUpperCase();
  const codes = Array.isArray(locale.available) ? locale.available : Object.keys(locale.available);

  const ghost =
    'inline-flex h-8 items-center rounded-md px-2.5 text-[13px] text-muted transition-colors hover:bg-black/[.04] hover:text-fg';

  return (
    <div className="sticky top-0 z-10 flex h-14 items-center gap-3 px-5 backdrop-blur-md bg-surface/70 border-b admin-sep">
      <div className="text-[13px] text-muted">{breadcrumb}</div>

      <div className="ml-auto flex items-center gap-0.5">
        <a href="/" target="_blank" rel="noreferrer" className={`${ghost} gap-1.5`}>
          {tr('cpanel/topbar.view_site', 'View site')}
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true" className="opacity-60">
            <path d="M7 17 17 7M9 7h8v8" />
          </svg>
        </a>

        <div className="relative">
          <select
            aria-label={tr('cpanel/topbar.language', 'Language')}
            value={locale.current}
            onChange={(e) => {
              // Full reload: the route sets the session locale and redirects
              // back, so the whole admin (incl. the i18n dictionary) comes back
              // in the new language reliably — an SPA visit to a redirect-back
              // does not consistently re-sync react-i18next.
              window.location.href = `${ADMIN}/locale/${e.target.value}`;
            }}
            className="h-8 cursor-pointer appearance-none rounded-md border border-[color:var(--border)] bg-surface bg-none !py-0 !pl-2.5 !pr-7 !text-[12px] font-medium !leading-none text-muted transition-colors hover:border-[color:var(--border-strong)] hover:text-fg"
          >
            {codes.map((code) => (
              <option key={code} value={code}>
                {code.toUpperCase()}
              </option>
            ))}
          </select>
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true" className="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-muted opacity-70">
            <path d="m6 9 6 6 6-6" />
          </svg>
        </div>

        <div className="mx-1.5 h-5 border-l admin-sep" aria-hidden="true" />

        <div className="flex items-center gap-1.5">
          <div className="grid h-7 w-7 place-items-center rounded-full bg-primary text-primary-contrast text-[11px] font-semibold" title={auth.user?.name ?? undefined}>
            {initials}
          </div>
          <Link href="/logout" method="post" as="button" className={ghost}>
            {tr('cpanel/topbar.logout', 'Log out')}
          </Link>
        </div>
      </div>
    </div>
  );
}

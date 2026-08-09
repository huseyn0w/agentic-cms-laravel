import type { ReactNode } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
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
            onChange={(e) => router.visit(`${ADMIN}/locale/${e.target.value}`)}
            className="h-8 cursor-pointer appearance-none rounded-md bg-transparent pl-2.5 pr-7 text-[12px] font-medium text-muted transition-colors hover:bg-black/[.04] hover:text-fg focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/25"
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

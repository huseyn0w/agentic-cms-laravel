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
  return (
    <div className="sticky top-0 z-10 flex h-14 items-center gap-3.5 px-5 backdrop-blur-md bg-surface/70 border-b admin-sep">
      <div className="text-[13px] text-muted">{breadcrumb}</div>
      <div className="ml-auto flex items-center gap-1.5">
        <a
          href="/"
          className="rounded-md px-2.5 py-1.5 text-[13px] text-muted transition-colors hover:bg-black/[.035] hover:text-fg"
        >
          {tr('cpanel/topbar.view_site', 'View site')}
        </a>
        <select
          aria-label={tr('cpanel/topbar.language', 'Language')}
          value={locale.current}
          onChange={(e) => router.visit(`${ADMIN}/locale/${e.target.value}`)}
          className="rounded-md border admin-sep bg-surface px-1.5 py-1 text-[12px] font-medium text-muted transition-colors hover:text-fg"
        >
          {codes.map((code) => (
            <option key={code} value={code}>
              {code.toUpperCase()}
            </option>
          ))}
        </select>
        <div className="grid h-[30px] w-[30px] place-items-center rounded-full bg-primary text-primary-contrast text-[11.5px] font-semibold">
          {initials}
        </div>
        <Link
          href="/logout"
          method="post"
          as="button"
          className="rounded-md px-2.5 py-1.5 text-[13px] text-muted transition-colors hover:bg-black/[.035] hover:text-fg"
        >
          {tr('cpanel/topbar.logout', 'Log out')}
        </Link>
      </div>
    </div>
  );
}

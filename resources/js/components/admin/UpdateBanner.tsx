import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import type { SharedProps } from '@/lib/types';

const ADMIN = '/agentic-cms-laravel-admin';

/**
 * Top-of-admin banner shown when a core update is available. The version comes
 * from the cached background check (cms.updateAvailable), which the server only
 * exposes to admins who can manage updates — so this simply renders when the
 * prop is present. Clicking through goes to the updates screen.
 */
export function UpdateBanner() {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const version = usePage<SharedProps>().props.cms?.updateAvailable;

  if (!version) {
    return null;
  }

  return (
    <div
      data-testid="update-banner"
      role="status"
      className="flex items-center gap-3 border-b admin-sep bg-surface-2 px-5 py-2.5 text-[13px]"
    >
      <span className="inline-flex h-1.5 w-1.5 shrink-0 rounded-full bg-[color:var(--accent-blue)]" aria-hidden="true" />
      <span className="text-fg">
        {tr('cpanel/updates.banner', 'A core update is available')}
        {' '}
        <span className="font-semibold tabular-nums">v{version}</span>
      </span>
      <Link
        href={`${ADMIN}/updates`}
        className="ml-auto rounded-md bg-primary px-2.5 py-1 text-[12px] font-semibold text-primary-contrast transition-colors hover:bg-primary-hover"
      >
        {tr('cpanel/updates.banner_cta', 'View update')}
      </Link>
    </div>
  );
}

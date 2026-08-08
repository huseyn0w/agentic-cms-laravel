import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export type SettingsTab = 'general' | 'site-options' | 'seo' | 'geo' | 'aeo';

const ADMIN = '/agentic-cms-laravel-admin';
const TABS: { key: SettingsTab; href: string; labelKey: string; fallback: string }[] = [
  { key: 'general', href: `${ADMIN}/general-settings`, labelKey: 'cpanel/settings.tab_general', fallback: 'General' },
  { key: 'site-options', href: `${ADMIN}/site-options`, labelKey: 'cpanel/settings.tab_site_options', fallback: 'Site options' },
  { key: 'seo', href: `${ADMIN}/seo-settings`, labelKey: 'cpanel/settings.tab_seo', fallback: 'SEO' },
  { key: 'geo', href: `${ADMIN}/geo-settings`, labelKey: 'cpanel/settings.tab_geo', fallback: 'GEO' },
  { key: 'aeo', href: `${ADMIN}/aeo-settings`, labelKey: 'cpanel/settings.tab_aeo', fallback: 'AEO' },
];

/**
 * Section nav for the four settings singletons. Uses Inertia <Link> throughout;
 * tabs still on Blade (seo/geo, until their slice lands) resolve as a normal
 * full-page visit, so the nav works during the transition.
 */
export function SettingsTabs({ active }: { active: SettingsTab }) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));

  return (
    <nav className="mb-6 flex flex-wrap gap-1 border-b admin-sep">
      {TABS.map((tab) => (
        <Link key={tab.key} href={tab.href} prefetch cacheFor="15s"
          aria-current={active === tab.key ? 'page' : undefined}
          className={`-mb-px border-b-2 px-3.5 py-2 text-[13px] font-semibold ${
            active === tab.key
              ? 'border-[color:var(--accent-blue)] text-fg'
              : 'border-transparent text-muted hover:text-fg'
          }`}>
          {tr(tab.labelKey, tab.fallback)}
        </Link>
      ))}
    </nav>
  );
}

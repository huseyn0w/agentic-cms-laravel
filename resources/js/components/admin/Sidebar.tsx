import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { NAV_GROUPS, type Ability } from '@/lib/admin-nav';
import type { SharedProps } from '@/lib/types';

export function Sidebar({ can }: { can: Record<Ability, boolean> }) {
  const { t } = useTranslation();
  const { component, props, url } = usePage<SharedProps>();
  const version = props.cms?.version;
  const contentTypes = props.contentTypes ?? [];
  const label = (key: string, fallback: string) => {
    const s = t(key);
    return s === key ? fallback : s;
  };
  return (
    <aside data-testid="admin-sidebar" className="theme-admin w-[236px] shrink-0 bg-surface-2 p-3 flex flex-col gap-1">
      <div className="flex items-center gap-2 px-2 py-3">
        <div className="h-7 w-7 grid place-items-center rounded-lg bg-primary text-primary-contrast font-bold text-sm">A</div>
        <b className="text-fg tracking-tight">Agentic CMS</b>
      </div>
      {NAV_GROUPS.map((group) => {
        const items = group.items.filter((i) => can[i.ability]);
        if (items.length === 0) return null;
        return (
          <div key={group.labelKey}>
            <div className="px-2 pt-3 pb-1 text-[10.5px] font-semibold uppercase tracking-wider text-faint">
              {label(group.labelKey, group.fallback)}
            </div>
            {items.map((item) => {
              const active = component === item.component || component.startsWith(`${item.component}/`);
              return (
                <Link key={item.key} href={item.href} prefetch="mount" cacheFor="15s"
                  className={`flex items-center gap-2.5 rounded-lg px-2.5 py-1.5 text-sm transition-colors ${
                    active ? 'bg-primary font-semibold text-primary-contrast' : 'text-muted hover:bg-black/[.035] hover:text-fg'
                  }`}>
                  {label(item.key, item.fallback)}
                </Link>
              );
            })}
          </div>
        );
      })}
      {contentTypes.length > 0 && (
        <div data-testid="content-types-group">
          <div className="px-2 pt-3 pb-1 text-[10.5px] font-semibold uppercase tracking-wider text-faint">
            {label('cpanel/menu.content_types', 'Content')}
          </div>
          {contentTypes.map((type) => {
            const href = `/agentic-cms-laravel-admin/content/${type.slug}`;
            const active = component === 'cpanel/content' && url.includes(`/content/${type.slug}`);
            return (
              <Link key={type.slug} href={href} prefetch="mount" cacheFor="15s"
                className={`flex items-center gap-2.5 rounded-lg px-2.5 py-1.5 text-sm transition-colors ${
                  active ? 'bg-primary font-semibold text-primary-contrast' : 'text-muted hover:bg-black/[.035] hover:text-fg'
                }`}>
                {type.label}
              </Link>
            );
          })}
        </div>
      )}
      {version && (
        <div
          data-testid="admin-version"
          className="mt-auto px-2.5 pt-3 text-[11px] font-medium tabular-nums text-faint"
        >
          v{version}
        </div>
      )}
    </aside>
  );
}

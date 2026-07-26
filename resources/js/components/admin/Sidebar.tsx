import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { NAV_GROUPS, type Ability } from '@/lib/admin-nav';

export function Sidebar({ can }: { can: Record<Ability, boolean> }) {
  const { t } = useTranslation();
  const { component } = usePage();
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
                <Link key={item.key} href={item.href} prefetch
                  className={`flex items-center gap-2.5 rounded-lg px-2.5 py-1.5 text-sm ${
                    active ? 'admin-nav-active font-semibold text-fg' : 'text-muted hover:bg-black/[.035] hover:text-fg'
                  }`}>
                  {label(item.key, item.fallback)}
                </Link>
              );
            })}
          </div>
        );
      })}
    </aside>
  );
}

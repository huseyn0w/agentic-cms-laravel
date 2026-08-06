import { Head, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import type { ReactElement } from 'react';

interface Plugin {
    slug: string;
    name: string;
    description: string;
    enabled: boolean;
}

interface ListProps {
    plugins: Plugin[];
}

const BASE = '/agentic-cms-laravel-admin/plugins';

export default function List({ plugins }: ListProps) {
    const { t } = useTranslation();
    const tr = (k: string, f: string) => (t(k) === k ? f : t(k));

    const toggle = (plugin: Plugin) =>
        router.put(`${BASE}/toggle`, { slug: plugin.slug, enabled: !plugin.enabled }, { preserveScroll: true });

    return (
        <>
            <Head title={tr('cpanel/plugins.headline', 'Plugins')} />
            <div className="mb-5">
                <h1 className="text-[22px] font-semibold tracking-tight">{tr('cpanel/plugins.headline', 'Plugins')}</h1>
                <p className="mt-1 text-[13px] text-muted">{tr('cpanel/plugins.intro', 'Enable or disable bundled plugins.')}</p>
            </div>

            <div className="admin-card overflow-hidden">
                <table className="w-full border-collapse text-[13.5px]">
                    <thead>
                        <tr className="text-left text-[11px] uppercase tracking-wide text-faint">
                            <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/plugins.plugin', 'Plugin')}</th>
                            <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/plugins.status', 'Status')}</th>
                            <th className="w-[120px] border-b admin-sep px-4 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody>
                        {plugins.length === 0 && (
                            <tr>
                                <td colSpan={3} className="border-b admin-sep px-4 py-8 text-center text-muted">
                                    {tr('cpanel/plugins.empty', 'No plugins found.')}
                                </td>
                            </tr>
                        )}
                        {plugins.map((plugin) => (
                            <tr key={plugin.slug} className="hover:bg-black/[.022]">
                                <td className="border-b admin-sep px-4 py-3">
                                    <div className="font-semibold">{plugin.name}</div>
                                    <div className="text-[12.5px] text-muted">{plugin.description}</div>
                                </td>
                                <td className="border-b admin-sep px-4 py-3">
                                    {plugin.enabled ? (
                                        <span className="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-[12px] font-medium text-primary">
                                            {tr('cpanel/plugins.enabled', 'Enabled')}
                                        </span>
                                    ) : (
                                        <span className="inline-flex items-center rounded-full bg-surface-2 px-2.5 py-0.5 text-[12px] font-medium text-muted">
                                            {tr('cpanel/plugins.disabled', 'Disabled')}
                                        </span>
                                    )}
                                </td>
                                <td className="border-b admin-sep px-4 py-3 text-right">
                                    <button
                                        onClick={() => toggle(plugin)}
                                        data-testid={`toggle-${plugin.slug}`}
                                        className={
                                            'inline-flex h-8 items-center rounded-[9px] px-3 text-[12.5px] font-semibold ' +
                                            (plugin.enabled ? 'text-muted hover:text-fg' : 'bg-primary text-primary-contrast')
                                        }
                                    >
                                        {plugin.enabled ? tr('cpanel/plugins.disable', 'Disable') : tr('cpanel/plugins.enable', 'Enable')}
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </>
    );
}

List.layout = (page: ReactElement) => <AdminLayout breadcrumb="Admin / Plugins">{page}</AdminLayout>;

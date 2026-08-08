import { Head, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import { SettingsTabs } from '@/components/admin/SettingsTabs';
import { Button } from '@/components/Button';
import type { FormEvent, ReactElement } from 'react';

interface CrawlerOption { key: string; label: string }
interface Props {
  ai_crawler_catalog: CrawlerOption[];
  ai_crawlers: Record<string, boolean>;
}

const ENDPOINT = '/agentic-cms-laravel-admin/aeo-settings';

export default function Aeo({ ai_crawler_catalog = [], ai_crawlers }: Props) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));

  const form = useForm<{ ai_crawlers: Record<string, boolean> }>({
    ai_crawlers: ai_crawlers ?? {},
  });

  const toggle = (key: string, allowed: boolean) =>
    form.setData('ai_crawlers', { ...form.data.ai_crawlers, [key]: allowed });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(ENDPOINT, { preserveScroll: true });
  };

  return (
    <>
      <Head title={tr('cpanel/settings.aeo_settings_headline', 'AEO Settings')} />
      <SettingsTabs active="aeo" />
      <form onSubmit={submit} className="mx-auto flex max-w-3xl flex-col gap-6">
        <div className="flex items-center gap-4">
          <h1 className="text-[22px] font-semibold tracking-tight">
            {tr('cpanel/settings.aeo_settings_headline', 'AEO Settings')}
          </h1>
          <Button type="submit" variant="primary" size="md" loading={form.processing}
            data-testid="aeo-submit" className="ml-auto">
            {tr('cpanel/settings.update_button_label', 'Update settings')}
          </Button>
        </div>

        <section className="admin-card flex flex-col gap-4 p-[18px]">
          <h3 className="text-[11px] font-semibold uppercase tracking-wide text-muted">
            {tr('cpanel/settings.aeo_crawlers_section', 'AI crawlers')}
          </h3>
          <p className="text-xs text-muted">
            {tr('cpanel/settings.aeo_crawlers_help', 'Answer-engine and AI crawlers are allowed by default. Turn one off to add a Disallow rule for it in robots.txt.')}
          </p>
          <div className="flex flex-col gap-2.5">
            {ai_crawler_catalog.map((bot) => (
              <label key={bot.key} className="flex cursor-pointer items-center gap-2.5 text-sm text-fg">
                <input type="checkbox" aria-label={`ai_crawler_${bot.key}`}
                  checked={Boolean(form.data.ai_crawlers[bot.key])}
                  onChange={(e) => toggle(bot.key, e.target.checked)} />
                {bot.label}
              </label>
            ))}
          </div>
        </section>
      </form>
    </>
  );
}

Aeo.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Settings / AEO">{page}</AdminLayout>
);

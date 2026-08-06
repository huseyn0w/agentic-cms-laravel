import { Head, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import { SettingsTabs } from '@/components/admin/SettingsTabs';
import { TextField } from '@/components/TextField';
import { Button } from '@/components/Button';
import type { FormEvent, ReactElement, ReactNode } from 'react';

interface SeoSettings {
  title_separator: string;
  default_meta_description: string;
  default_og_image: string;
  og_site_name: string;
  twitter_handle: string;
  google_site_verification: string;
  bing_site_verification: string;
  ga4_measurement_id: string;
  gtm_container_id: string;
  discourage_search_engines: boolean;
  sitemap_enabled: boolean;
  robots_extra: string;
  ai_crawlers: Record<string, boolean>;
}
interface CrawlerOption { key: string; label: string }
interface Props {
  seo_settings: SeoSettings;
  ai_crawler_catalog: CrawlerOption[];
}

const ENDPOINT = '/agentic-cms-laravel-admin/seo-settings';

function Card({ title, children }: { title: string; children: ReactNode }) {
  return (
    <section className="admin-card flex flex-col gap-4 p-[18px]">
      <h3 className="text-[11px] font-semibold uppercase tracking-wide text-muted">{title}</h3>
      {children}
    </section>
  );
}

export default function Seo({ seo_settings, ai_crawler_catalog = [] }: Props) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));

  const form = useForm({
    ai_crawlers: seo_settings.ai_crawlers ?? {},
    title_separator: seo_settings.title_separator ?? '',
    default_meta_description: seo_settings.default_meta_description ?? '',
    default_og_image: seo_settings.default_og_image ?? '',
    og_site_name: seo_settings.og_site_name ?? '',
    twitter_handle: seo_settings.twitter_handle ?? '',
    google_site_verification: seo_settings.google_site_verification ?? '',
    bing_site_verification: seo_settings.bing_site_verification ?? '',
    ga4_measurement_id: seo_settings.ga4_measurement_id ?? '',
    gtm_container_id: seo_settings.gtm_container_id ?? '',
    discourage_search_engines: Boolean(seo_settings.discourage_search_engines),
    sitemap_enabled: Boolean(seo_settings.sitemap_enabled),
    robots_extra: seo_settings.robots_extra ?? '',
  });

  const field = (name: keyof SeoSettings, labelKey: string, fallback: string, placeholder?: string) => (
    <TextField name={name} label={tr(labelKey, fallback)} data-testid={name} placeholder={placeholder}
      value={String(form.data[name] ?? '')} error={form.errors[name]}
      onChange={(e) => form.setData(name, e.target.value)} />
  );

  const toggleCrawler = (key: string, allowed: boolean) =>
    form.setData('ai_crawlers', { ...form.data.ai_crawlers, [key]: allowed });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(ENDPOINT, { preserveScroll: true });
  };

  return (
    <>
      <Head title={tr('cpanel/settings.seo_settings_headline', 'SEO Settings')} />
      <SettingsTabs active="seo" />
      <form onSubmit={submit} className="mx-auto flex max-w-3xl flex-col gap-6">
        <div className="flex items-center gap-4">
          <h1 className="text-[22px] font-semibold tracking-tight">
            {tr('cpanel/settings.seo_settings_headline', 'SEO Settings')}
          </h1>
          <Button type="submit" variant="primary" size="md" loading={form.processing}
            data-testid="seo-submit" className="ml-auto">
            {tr('cpanel/settings.update_button_label', 'Update settings')}
          </Button>
        </div>

        <Card title={tr('cpanel/settings.seo_meta_section', 'Meta defaults')}>
          {field('title_separator', 'cpanel/settings.seo_title_separator', 'Title separator')}
          <div className="flex flex-col gap-y-1.5">
            <label htmlFor="default_meta_description" className="font-sans text-sm font-medium text-fg">
              {tr('cpanel/settings.seo_default_description', 'Default meta description')}
            </label>
            <textarea id="default_meta_description" rows={3} className="field-input w-full"
              value={form.data.default_meta_description}
              onChange={(e) => form.setData('default_meta_description', e.target.value)} />
          </div>
          {field('default_og_image', 'cpanel/settings.seo_default_og_image', 'Default OG image URL', 'https://...')}
        </Card>

        <Card title={tr('cpanel/settings.seo_social_section', 'Social')}>
          {field('og_site_name', 'cpanel/settings.seo_og_site_name', 'OG site name')}
          {field('twitter_handle', 'cpanel/settings.seo_twitter_handle', 'Twitter handle', '@yourhandle')}
        </Card>

        <Card title={tr('cpanel/settings.seo_verification_section', 'Search-engine verification')}>
          {field('google_site_verification', 'cpanel/settings.seo_google_verification', 'Google Search Console')}
          {field('bing_site_verification', 'cpanel/settings.seo_bing_verification', 'Bing')}
        </Card>

        <Card title={tr('cpanel/settings.seo_analytics_section', 'Analytics (optional)')}>
          <p className="text-xs text-muted">{tr('cpanel/settings.seo_analytics_help', '')}</p>
          {field('ga4_measurement_id', 'cpanel/settings.seo_ga4_id', 'Google Analytics 4 ID', 'G-XXXXXXX')}
          {field('gtm_container_id', 'cpanel/settings.seo_gtm_id', 'Google Tag Manager ID', 'GTM-XXXXXXX')}
        </Card>

        <Card title={tr('cpanel/settings.seo_indexing_section', 'Indexing, robots & sitemap')}>
          <label className="flex cursor-pointer items-center gap-2.5 text-sm text-fg">
            <input type="checkbox" aria-label="discourage_search_engines"
              checked={form.data.discourage_search_engines}
              onChange={(e) => form.setData('discourage_search_engines', e.target.checked)} />
            {tr('cpanel/settings.seo_discourage', 'Discourage search engines from indexing this site')}
          </label>
          <label className="flex cursor-pointer items-center gap-2.5 text-sm text-fg">
            <input type="checkbox" aria-label="sitemap_enabled" checked={form.data.sitemap_enabled}
              onChange={(e) => form.setData('sitemap_enabled', e.target.checked)} />
            {tr('cpanel/settings.seo_sitemap_enabled', 'Enable sitemap.xml')}
          </label>
          <div className="flex flex-col gap-y-1.5">
            <label htmlFor="robots_extra" className="font-sans text-sm font-medium text-fg">
              {tr('cpanel/settings.seo_robots_extra', 'robots.txt extra lines')}
            </label>
            <textarea id="robots_extra" rows={4} className="field-input w-full font-mono text-sm"
              value={form.data.robots_extra} onChange={(e) => form.setData('robots_extra', e.target.value)} />
          </div>
        </Card>

        <Card title={tr('cpanel/settings.seo_ai_crawlers_section', 'AI crawlers')}>
          <p className="text-xs text-muted">
            {tr('cpanel/settings.seo_ai_crawlers_help', 'Every bot is allowed by default. Turn one off to add a Disallow rule for it in robots.txt.')}
          </p>
          <div className="flex flex-col gap-2.5">
            {ai_crawler_catalog.map((bot) => (
              <label key={bot.key} className="flex cursor-pointer items-center gap-2.5 text-sm text-fg">
                <input type="checkbox" aria-label={`ai_crawler_${bot.key}`}
                  checked={Boolean(form.data.ai_crawlers[bot.key])}
                  onChange={(e) => toggleCrawler(bot.key, e.target.checked)} />
                {bot.label}
              </label>
            ))}
          </div>
        </Card>
      </form>
    </>
  );
}

Seo.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Settings / SEO">{page}</AdminLayout>
);

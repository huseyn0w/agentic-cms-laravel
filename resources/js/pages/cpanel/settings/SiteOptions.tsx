import { Head, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import { SettingsTabs } from '@/components/admin/SettingsTabs';
import { TextField } from '@/components/TextField';
import { Button } from '@/components/Button';
import { MediaField } from '@/components/MediaField';
import type { FormEvent, ReactElement } from 'react';

interface SiteOptionsData {
  logo_url: string;
  copyright: string;
  linkedin_url: string;
  github_url: string;
}
interface Props {
  site_options: SiteOptionsData;
}

const ENDPOINT = '/agentic-cms-laravel-admin/site-options';

export default function SiteOptions({ site_options }: Props) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));

  const form = useForm({
    logo_url: site_options.logo_url ?? '',
    copyright: site_options.copyright ?? '',
    linkedin_url: site_options.linkedin_url ?? '',
    github_url: site_options.github_url ?? '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(ENDPOINT, { preserveScroll: true });
  };

  return (
    <>
      <Head title={tr('cpanel/settings.site_options_headline', 'Site options')} />
      <SettingsTabs active="site-options" />
      <form onSubmit={submit} className="mx-auto max-w-3xl">
        <div className="mb-5 flex items-center gap-4">
          <h1 className="text-[22px] font-semibold tracking-tight">
            {tr('cpanel/settings.site_options_headline', 'Site options')}
          </h1>
          <Button type="submit" variant="primary" size="md" loading={form.processing}
            data-testid="site-options-submit" className="ml-auto">
            {tr('cpanel/settings.update_button_label', 'Update settings')}
          </Button>
        </div>

        <section className="admin-card flex flex-col gap-4 p-[18px]">
          <MediaField label={tr('cpanel/settings.logo', 'Logo')}
            value={form.data.logo_url} onChange={(url) => form.setData('logo_url', url)} />
          {form.errors.logo_url && <p className="text-xs text-error">{form.errors.logo_url}</p>}

          <TextField name="copyright" label={tr('cpanel/settings.footer_copyright', 'Footer copyright')} required
            data-testid="copyright" value={form.data.copyright} error={form.errors.copyright}
            onChange={(e) => form.setData('copyright', e.target.value)} />

          <TextField name="linkedin_url" label={tr('cpanel/settings.linkedin_url', 'LinkedIn URL')} required
            data-testid="linkedin_url" value={form.data.linkedin_url} error={form.errors.linkedin_url}
            onChange={(e) => form.setData('linkedin_url', e.target.value)} />

          <TextField name="github_url" label={tr('cpanel/settings.github_url', 'GitHub URL')} required
            data-testid="github_url" value={form.data.github_url} error={form.errors.github_url}
            onChange={(e) => form.setData('github_url', e.target.value)} />
        </section>
      </form>
    </>
  );
}

SiteOptions.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Settings / Site options">{page}</AdminLayout>
);

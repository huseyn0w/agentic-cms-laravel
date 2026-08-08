import { Head, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import { SettingsTabs } from '@/components/admin/SettingsTabs';
import { TextField } from '@/components/TextField';
import { Button } from '@/components/Button';
import type { FormEvent, ReactElement, ReactNode } from 'react';

interface ThemeSettings {
  site_title: string | null;
  accent_color: string | null;
  font_family: string | null;
  radius: number | null;
}
interface Props {
  theme_settings: ThemeSettings;
}

const ENDPOINT = '/agentic-cms-laravel-admin/theme-settings';

function Card({ title, children }: { title: string; children: ReactNode }) {
  return (
    <section className="admin-card flex flex-col gap-4 p-[18px]">
      <h3 className="text-[11px] font-semibold uppercase tracking-wide text-muted">{title}</h3>
      {children}
    </section>
  );
}

export default function Theme({ theme_settings }: Props) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));

  const form = useForm({
    site_title: theme_settings.site_title ?? '',
    accent_color: theme_settings.accent_color ?? '',
    font_family: theme_settings.font_family ?? '',
    radius: theme_settings.radius != null ? String(theme_settings.radius) : '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(ENDPOINT, { preserveScroll: true });
  };

  // The colour input and a text input stay in sync so an admin can paste a hex
  // or use the native picker; both write the same accent_color field.
  const accent = form.data.accent_color || '#6d28d9';

  return (
    <>
      <Head title={tr('cpanel/settings.theme_settings_headline', 'Theme Settings')} />
      <SettingsTabs active="theme" />
      <form onSubmit={submit} className="mx-auto flex max-w-3xl flex-col gap-6">
        <div className="flex items-center gap-4">
          <h1 className="text-[22px] font-semibold tracking-tight">
            {tr('cpanel/settings.theme_settings_headline', 'Theme Settings')}
          </h1>
          <Button type="submit" variant="primary" size="md" loading={form.processing}
            data-testid="theme-submit" className="ml-auto">
            {tr('cpanel/settings.update_button_label', 'Update settings')}
          </Button>
        </div>

        <p className="text-sm text-muted">
          {tr('cpanel/settings.theme_intro', 'Re-skin the public site without a rebuild. These values are applied as CSS variables on every page.')}
        </p>

        <Card title={tr('cpanel/settings.theme_brand_section', 'Brand')}>
          <TextField name="site_title" label={tr('cpanel/settings.theme_site_title', 'Site title')}
            data-testid="theme-site-title" value={form.data.site_title}
            error={form.errors.site_title}
            onChange={(e) => form.setData('site_title', e.target.value)} />
        </Card>

        <Card title={tr('cpanel/settings.theme_appearance_section', 'Appearance')}>
          <div className="flex flex-col gap-y-1.5">
            <label htmlFor="accent_color" className="font-sans text-sm font-medium text-fg">
              {tr('cpanel/settings.theme_accent_color', 'Accent colour')}
            </label>
            <div className="flex items-center gap-3">
              <input type="color" aria-label="accent_color_picker" data-testid="theme-accent-picker"
                value={/^#([0-9a-fA-F]{6})$/.test(accent) ? accent : '#6d28d9'}
                onChange={(e) => form.setData('accent_color', e.target.value)}
                className="h-9 w-12 cursor-pointer rounded border admin-sep bg-surface p-1" />
              <input id="accent_color" name="accent_color" data-testid="theme-accent-color"
                className="field-input w-full" placeholder="#6d28d9"
                value={form.data.accent_color}
                onChange={(e) => form.setData('accent_color', e.target.value)} />
            </div>
            {form.errors.accent_color && (
              <p className="text-sm text-[color:var(--error)]">{form.errors.accent_color}</p>
            )}
          </div>

          <TextField name="font_family" label={tr('cpanel/settings.theme_font_family', 'Font family')}
            data-testid="theme-font-family" value={form.data.font_family}
            error={form.errors.font_family} placeholder="Geist, system-ui, sans-serif"
            onChange={(e) => form.setData('font_family', e.target.value)} />

          <TextField name="radius" label={tr('cpanel/settings.theme_radius', 'Corner radius (px)')}
            data-testid="theme-radius" type="number" value={form.data.radius}
            error={form.errors.radius}
            onChange={(e) => form.setData('radius', e.target.value)} />
        </Card>
      </form>
    </>
  );
}

Theme.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Settings / Theme">{page}</AdminLayout>
);

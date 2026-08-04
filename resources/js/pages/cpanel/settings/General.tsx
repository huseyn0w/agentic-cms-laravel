import { Head, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import { SettingsTabs } from '@/components/admin/SettingsTabs';
import { TextField } from '@/components/TextField';
import { Button } from '@/components/Button';
import type { FormEvent, ReactElement } from 'react';

interface GeneralSettings {
  website_name: string;
  tagline: string;
  contact_email: string;
  membership: boolean;
  email_verification: boolean;
  active_template_name: string;
  posts_per_page: number;
  comments_per_page: number;
}
interface Props {
  general_settings: GeneralSettings;
  templates: string[];
}

const ENDPOINT = '/agentic-cms-laravel-admin/general-settings';

export default function General({ general_settings, templates }: Props) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));

  const form = useForm({
    website_name: general_settings.website_name ?? '',
    tagline: general_settings.tagline ?? '',
    contact_email: general_settings.contact_email ?? '',
    membership: Boolean(general_settings.membership),
    email_verification: Boolean(general_settings.email_verification),
    active_template_name: general_settings.active_template_name ?? '',
    posts_per_page: general_settings.posts_per_page ?? 10,
    comments_per_page: general_settings.comments_per_page ?? 10,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(ENDPOINT, { preserveScroll: true });
  };

  return (
    <>
      <Head title={tr('cpanel/settings.general_settings_headline', 'Website settings')} />
      <SettingsTabs active="general" />
      <form onSubmit={submit} className="mx-auto max-w-3xl">
        <div className="mb-5 flex items-center gap-4">
          <h1 className="text-[22px] font-semibold tracking-tight">
            {tr('cpanel/settings.general_settings_headline', 'Website settings')}
          </h1>
          <Button type="submit" variant="primary" size="md" loading={form.processing}
            data-testid="general-submit" className="ml-auto">
            {tr('cpanel/settings.update_button_label', 'Update settings')}
          </Button>
        </div>

        <section className="admin-card flex flex-col gap-4 p-[18px]">
          <TextField name="website_name" label={tr('cpanel/settings.website_name', 'Website name')} required
            data-testid="website_name" value={form.data.website_name} error={form.errors.website_name}
            onChange={(e) => form.setData('website_name', e.target.value)} />

          <div className="flex flex-col gap-y-1.5">
            <label htmlFor="tagline" className="font-sans text-sm font-medium text-fg">
              {tr('cpanel/settings.tagline', 'Tagline')}
            </label>
            <textarea id="tagline" name="tagline" rows={3} className="field-input w-full"
              value={form.data.tagline} onChange={(e) => form.setData('tagline', e.target.value)} />
            {form.errors.tagline && <p className="text-xs text-error">{form.errors.tagline}</p>}
          </div>

          <TextField name="contact_email" type="email" label={tr('cpanel/settings.contact_email', 'Contact email')}
            required data-testid="contact_email" value={form.data.contact_email} error={form.errors.contact_email}
            onChange={(e) => form.setData('contact_email', e.target.value)} />

          <div className="flex flex-col gap-2">
            <label className="flex cursor-pointer items-center gap-2.5 text-sm text-fg">
              <input type="checkbox" aria-label="membership" checked={form.data.membership}
                onChange={(e) => form.setData('membership', e.target.checked)} />
              {tr('cpanel/settings.membership', 'Membership')}
            </label>
            <label className="flex cursor-pointer items-center gap-2.5 text-sm text-fg">
              <input type="checkbox" aria-label="email_verification" checked={form.data.email_verification}
                onChange={(e) => form.setData('email_verification', e.target.checked)} />
              {tr('cpanel/settings.email_verification', 'Require email verification for new members')}
            </label>
          </div>

          <div className="flex flex-col gap-y-1.5">
            <label htmlFor="active_template_name" className="font-sans text-sm font-medium text-fg">
              {tr('cpanel/settings.active_template', 'Active template')}
            </label>
            <select id="active_template_name" name="active_template_name" aria-label="active_template_name"
              className="field-input w-full" value={form.data.active_template_name}
              onChange={(e) => form.setData('active_template_name', e.target.value)}>
              {templates.length === 0 && <option value="">{tr('cpanel/settings.no_template', 'No templates')}</option>}
              {templates.map((tmpl) => (
                <option key={tmpl} value={tmpl}>{tmpl}</option>
              ))}
            </select>
          </div>

          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <TextField name="posts_per_page" type="number" min={1}
              label={tr('cpanel/settings.posts_per_page', 'Posts per page')} required
              value={String(form.data.posts_per_page)} error={form.errors.posts_per_page}
              onChange={(e) => form.setData('posts_per_page', Number(e.target.value))} />
            <TextField name="comments_per_page" type="number" min={1}
              label={tr('cpanel/settings.comments_per_page', 'Comments per page')} required
              value={String(form.data.comments_per_page)} error={form.errors.comments_per_page}
              onChange={(e) => form.setData('comments_per_page', Number(e.target.value))} />
          </div>
        </section>
      </form>
    </>
  );
}

General.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Settings / General">{page}</AdminLayout>
);

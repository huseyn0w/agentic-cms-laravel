import { Head, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import { SettingsTabs } from '@/components/admin/SettingsTabs';
import { TextField } from '@/components/TextField';
import { Button } from '@/components/Button';
import type { FormEvent, ReactElement, ReactNode } from 'react';

interface GeoSettings {
  business_name: string;
  business_type: string;
  description: string;
  founder_name: string;
  services: string;
  service_area: string;
  contact_email: string;
  contact_phone: string;
  address: string;
  same_as: string;
  faq: string;
  emit_jsonld: boolean;
  include_in_llms: boolean;
}
interface Props {
  geo_settings: GeoSettings;
}

const ENDPOINT = '/agentic-cms-laravel-admin/geo-settings';
const TYPES = ['Organization', 'LocalBusiness', 'ProfessionalService', 'Person'] as const;

function Card({ title, children }: { title: string; children: ReactNode }) {
  return (
    <section className="admin-card flex flex-col gap-4 p-[18px]">
      <h3 className="text-[11px] font-semibold uppercase tracking-wide text-muted">{title}</h3>
      {children}
    </section>
  );
}

export default function Geo({ geo_settings }: Props) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));

  const form = useForm({
    business_name: geo_settings.business_name ?? '',
    business_type: geo_settings.business_type ?? 'Organization',
    description: geo_settings.description ?? '',
    founder_name: geo_settings.founder_name ?? '',
    services: geo_settings.services ?? '',
    service_area: geo_settings.service_area ?? '',
    contact_email: geo_settings.contact_email ?? '',
    contact_phone: geo_settings.contact_phone ?? '',
    address: geo_settings.address ?? '',
    same_as: geo_settings.same_as ?? '',
    faq: geo_settings.faq ?? '',
    emit_jsonld: Boolean(geo_settings.emit_jsonld),
    include_in_llms: Boolean(geo_settings.include_in_llms),
  });

  // e2e (tests/Browser/GeoSettingsTest.php) selects fields by a geo-<dashed>
  // data-testid, e.g. business_name -> geo-business-name.
  const testid = (name: keyof GeoSettings) => `geo-${String(name).replace(/_/g, '-')}`;

  const field = (name: keyof GeoSettings, labelKey: string, fallback: string) => (
    <TextField name={name} label={tr(labelKey, fallback)} data-testid={testid(name)}
      value={String(form.data[name] ?? '')} error={form.errors[name]}
      onChange={(e) => form.setData(name, e.target.value)} />
  );

  const textarea = (name: keyof GeoSettings, labelKey: string, fallback: string, rows = 4) => (
    <div className="flex flex-col gap-y-1.5">
      <label htmlFor={name} className="font-sans text-sm font-medium text-fg">{tr(labelKey, fallback)}</label>
      <textarea id={name} name={name} data-testid={testid(name)} rows={rows} className="field-input w-full"
        value={String(form.data[name] ?? '')} onChange={(e) => form.setData(name, e.target.value)} />
    </div>
  );

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(ENDPOINT, { preserveScroll: true });
  };

  return (
    <>
      <Head title={tr('cpanel/settings.geo_settings_headline', 'GEO Settings')} />
      <SettingsTabs active="geo" />
      <form onSubmit={submit} className="mx-auto flex max-w-3xl flex-col gap-6">
        <div className="flex items-center gap-4">
          <h1 className="text-[22px] font-semibold tracking-tight">
            {tr('cpanel/settings.geo_settings_headline', 'GEO Settings')}
          </h1>
          <Button type="submit" variant="primary" size="md" loading={form.processing}
            data-testid="geo-submit" className="ml-auto">
            {tr('cpanel/settings.update_button_label', 'Update settings')}
          </Button>
        </div>

        <Card title={tr('cpanel/settings.geo_identity_section', 'Identity')}>
          {field('business_name', 'cpanel/settings.geo_business_name', 'Business / brand name')}
          <div className="flex flex-col gap-y-1.5">
            <label htmlFor="business_type" className="font-sans text-sm font-medium text-fg">
              {tr('cpanel/settings.geo_business_type', 'Entity type')}
            </label>
            <select id="business_type" name="business_type" aria-label="business_type" data-testid="geo-business-type"
              className="field-input w-full" value={form.data.business_type}
              onChange={(e) => form.setData('business_type', e.target.value)}>
              {TYPES.map((type) => (
                <option key={type} value={type}>{tr(`cpanel/settings.geo_type_${type.toLowerCase()}`, type)}</option>
              ))}
            </select>
          </div>
          {textarea('description', 'cpanel/settings.geo_description', 'Short description', 3)}
          {field('founder_name', 'cpanel/settings.geo_founder_name', 'Founder / expert name')}
        </Card>

        <Card title={tr('cpanel/settings.geo_services_section', 'Services & reach')}>
          {textarea('services', 'cpanel/settings.geo_services', 'Services offered (one per line)', 5)}
          {field('service_area', 'cpanel/settings.geo_service_area', 'Service area / geography')}
        </Card>

        <Card title={tr('cpanel/settings.geo_contact_section', 'Contact')}>
          {field('contact_email', 'cpanel/settings.geo_contact_email', 'Contact email')}
          {field('contact_phone', 'cpanel/settings.geo_contact_phone', 'Contact phone')}
          {field('address', 'cpanel/settings.geo_address', 'Address')}
        </Card>

        <Card title={tr('cpanel/settings.geo_authority_section', 'Authority & citations')}>
          {textarea('same_as', 'cpanel/settings.geo_same_as', 'Profile / social URLs (one per line)')}
        </Card>

        <Card title={tr('cpanel/settings.geo_faq_section', 'FAQ')}>
          {textarea('faq', 'cpanel/settings.geo_faq', 'Questions & answers (one per line)', 5)}
        </Card>

        <Card title={tr('cpanel/settings.geo_output_section', 'Output')}>
          <label className="flex cursor-pointer items-center gap-2.5 text-sm text-fg">
            <input type="checkbox" name="emit_jsonld" aria-label="emit_jsonld" data-testid="geo-emit-jsonld"
              checked={form.data.emit_jsonld}
              onChange={(e) => form.setData('emit_jsonld', e.target.checked)} />
            {tr('cpanel/settings.geo_emit_jsonld', 'Emit JSON-LD structured data on the homepage')}
          </label>
          <label className="flex cursor-pointer items-center gap-2.5 text-sm text-fg">
            <input type="checkbox" name="include_in_llms" aria-label="include_in_llms" data-testid="geo-include-in-llms"
              checked={form.data.include_in_llms}
              onChange={(e) => form.setData('include_in_llms', e.target.checked)} />
            {tr('cpanel/settings.geo_include_in_llms', 'Include this in /llms.txt')}
          </label>
        </Card>
      </form>
    </>
  );
}

Geo.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Settings / GEO">{page}</AdminLayout>
);

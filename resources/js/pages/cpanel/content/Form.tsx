import { Head, Link, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import { TextField } from '@/components/TextField';
import { RichText } from '@/components/RichText';
import { MediaField } from '@/components/MediaField';
import { Button } from '@/components/Button';
import type { FormEvent, ReactElement } from 'react';

interface FieldDef {
  name: string;
  label: string;
  type: string;
  options: Record<string, string>;
}
interface ContentTypeDef {
  slug: string;
  label: string;
  fields: FieldDef[];
  columns: string[];
}
interface Props {
  type: ContentTypeDef;
  record: (Record<string, unknown> & { id: number }) | null;
}

const BASE = '/agentic-cms-laravel-admin/content';

type FieldValue = string | boolean;

function initial(fields: FieldDef[], record: Props['record']): Record<string, FieldValue> {
  const data: Record<string, FieldValue> = {};
  for (const f of fields) {
    const existing = record ? record[f.name] : undefined;
    data[f.name] = f.type === 'boolean' ? Boolean(existing) : existing == null ? '' : String(existing);
  }
  return data;
}

export default function Form({ type, record }: Props) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const base = `${BASE}/${type.slug}`;
  const form = useForm<Record<string, FieldValue>>(initial(type.fields, record));

  const submit = (e: FormEvent) => {
    e.preventDefault();
    if (record) form.put(`${base}/${record.id}`, { preserveScroll: true });
    else form.post(base, { preserveScroll: true });
  };

  const value = (name: string) => form.data[name];
  const set = (name: string, v: FieldValue) => form.setData(name, v);

  const renderField = (f: FieldDef) => {
    const err = form.errors[f.name] as string | undefined;
    const testid = `content-field-${f.name}`;

    if (f.type === 'richtext') {
      return (
        <div key={f.name} className="flex flex-col gap-y-1.5">
          <label className="font-sans text-sm font-medium text-fg">{f.label}</label>
          <RichText id={f.name} name={f.name} value={String(value(f.name) ?? '')}
            onChange={(html) => set(f.name, html)} height={300} />
          {err && <p className="text-xs text-error">{err}</p>}
        </div>
      );
    }
    if (f.type === 'image') {
      return (
        <MediaField key={f.name} label={f.label} value={String(value(f.name) ?? '')}
          onChange={(url) => set(f.name, url)} />
      );
    }
    if (f.type === 'textarea') {
      return (
        <div key={f.name} className="flex flex-col gap-y-1.5">
          <label htmlFor={f.name} className="font-sans text-sm font-medium text-fg">{f.label}</label>
          <textarea id={f.name} name={f.name} rows={4} data-testid={testid} className="field-input w-full"
            value={String(value(f.name) ?? '')} onChange={(e) => set(f.name, e.target.value)} />
          {err && <p className="text-xs text-error">{err}</p>}
        </div>
      );
    }
    if (f.type === 'boolean') {
      return (
        <label key={f.name} className="flex cursor-pointer items-center gap-2.5 text-sm text-fg">
          <input type="checkbox" name={f.name} data-testid={testid}
            checked={Boolean(value(f.name))} onChange={(e) => set(f.name, e.target.checked)} />
          {f.label}
        </label>
      );
    }
    if (f.type === 'select') {
      return (
        <div key={f.name} className="flex flex-col gap-y-1.5">
          <label htmlFor={f.name} className="font-sans text-sm font-medium text-fg">{f.label}</label>
          <select id={f.name} name={f.name} data-testid={testid} className="field-input w-full"
            value={String(value(f.name) ?? '')} onChange={(e) => set(f.name, e.target.value)}>
            {Object.entries(f.options).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
          </select>
          {err && <p className="text-xs text-error">{err}</p>}
        </div>
      );
    }

    const inputType = f.type === 'number' ? 'number' : f.type === 'date' ? 'date' : 'text';
    return (
      <TextField key={f.name} name={f.name} label={f.label} type={inputType} data-testid={testid}
        value={String(value(f.name) ?? '')} error={err}
        onChange={(e) => set(f.name, e.target.value)} />
    );
  };

  return (
    <>
      <Head title={type.label} />
      <form onSubmit={submit} className="mx-auto flex max-w-2xl flex-col gap-5">
        <div className="flex items-center gap-3">
          <Link href={base} className="text-[13px] text-muted hover:text-fg">← {type.label}</Link>
          <Button type="submit" variant="primary" size="md" loading={form.processing}
            data-testid="content-submit" className="ml-auto">
            {tr('cpanel/content.save', 'Save')}
          </Button>
        </div>

        <section className="admin-card flex flex-col gap-4 p-[18px]">
          {type.fields.map(renderField)}
        </section>
      </form>
    </>
  );
}

Form.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Content">{page}</AdminLayout>
);

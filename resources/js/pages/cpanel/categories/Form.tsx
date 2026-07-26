import { Head, useForm, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import { TextField } from '@/components/TextField';
import { Button } from '@/components/Button';
import type { SharedProps } from '@/lib/types';
import type { FormEvent, ReactElement } from 'react';

interface CategoryEntity {
  id: number; title: string; slug: string; description: string | null;
  parent_category_id: number | null; meta_description: string | null; meta_keywords: string | null;
}
interface ParentOption { category_id: number; title: string; depth: number }
interface FormProps {
  entity: CategoryEntity | null;
  parent_options: ParentOption[];
  translation_links: Record<string, string>;
}

const BASE = '/agentic-cms-laravel-admin/categories';

export default function Form({ entity, parent_options, translation_links }: FormProps) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const { locale } = usePage<SharedProps>().props;

  const form = useForm({
    title: entity?.title ?? '',
    slug: entity?.slug ?? '',
    parent_category_id: entity?.parent_category_id ?? '',
    description: entity?.description ?? '',
    meta_description: entity?.meta_description ?? '',
    meta_keywords: entity?.meta_keywords ?? '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    if (entity) form.put(`${BASE}/${entity.id}/update`);
    else form.post(`${BASE}/new`);
  };

  const heading = entity ? tr('cpanel/categories.edit', 'Edit category') : tr('cpanel/categories.add_new', 'New category');

  return (
    <>
      <Head title={heading} />
      <form onSubmit={submit}>
        <div className="mb-5 flex items-center gap-4">
          <h1 className="text-[22px] font-semibold tracking-tight">{heading}</h1>
          <div className="ml-auto flex items-center gap-2.5">
            {Object.entries(translation_links).length > 0 && (
              <div className="inline-flex gap-1 rounded-[10px] admin-bevel p-1">
                <span className="rounded-md bg-primary px-2.5 py-1 text-xs font-semibold text-primary-contrast uppercase">
                  {locale.current}
                </span>
                {Object.entries(translation_links).map(([title, href]) => (
                  <a key={href} href={`/${href}`} className="rounded-md px-2.5 py-1 text-xs font-semibold text-muted uppercase">
                    {title.slice(0, 2)}
                  </a>
                ))}
              </div>
            )}
            <Button href={BASE} variant="outline" size="md">{tr('cpanel/categories.cancel', 'Cancel')}</Button>
            <Button type="submit" variant="primary" size="md" loading={form.processing} data-testid="category-submit">
              {tr('cpanel/categories.save', 'Save')}
            </Button>
          </div>
        </div>

        <div className="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_300px]">
          <section className="admin-card p-[18px] flex flex-col gap-4">
            <TextField name="title" label={tr('cpanel/categories.title', 'Title')} required
              data-testid="category-title" value={form.data.title} error={form.errors.title}
              onChange={(e) => form.setData('title', e.target.value)} />
            <TextField name="slug" label={tr('cpanel/categories.slug', 'Slug')} required
              data-testid="category-slug" value={form.data.slug} error={form.errors.slug}
              onChange={(e) => form.setData('slug', e.target.value)} />
            <div>
              <label htmlFor="parent_category_id" className="mb-1.5 block text-xs font-semibold text-fg">
                {tr('cpanel/categories.parent', 'Parent category')}
              </label>
              <select id="parent_category_id" name="parent_category_id"
                className="field-input w-full" value={form.data.parent_category_id}
                onChange={(e) => form.setData('parent_category_id', e.target.value)}>
                <option value="">{tr('cpanel/categories.no_parent', '— None (top level) —')}</option>
                {parent_options.map((o) => (
                  <option key={o.category_id} value={o.category_id}>
                    {'  '.repeat(o.depth)}{o.title}
                  </option>
                ))}
              </select>
              {form.errors.parent_category_id && (
                <p className="mt-1.5 text-xs text-error">{form.errors.parent_category_id}</p>
              )}
            </div>
            <div>
              <label htmlFor="description" className="mb-1.5 block text-xs font-semibold text-fg">
                {tr('cpanel/categories.description', 'Description')}
              </label>
              <textarea id="description" name="description" className="field-input min-h-[88px] w-full"
                value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} />
              {form.errors.description && (
                <p className="mt-1.5 text-xs text-error">{form.errors.description}</p>
              )}
            </div>
          </section>

          <section className="admin-card p-[18px] flex flex-col gap-4">
            <h3 className="text-[13px] font-semibold">{tr('cpanel/categories.seo', 'SEO')}</h3>
            <div>
              <label htmlFor="meta_description" className="mb-1.5 block text-xs font-semibold text-fg">
                {tr('cpanel/categories.meta_description', 'Meta description')}
              </label>
              <textarea id="meta_description" name="meta_description" className="field-input min-h-[70px] w-full"
                value={form.data.meta_description} onChange={(e) => form.setData('meta_description', e.target.value)} />
              {form.errors.meta_description && (
                <p className="mt-1.5 text-xs text-error">{form.errors.meta_description}</p>
              )}
            </div>
            <TextField name="meta_keywords" label={tr('cpanel/categories.meta_keywords', 'Meta keywords')}
              value={form.data.meta_keywords} error={form.errors.meta_keywords}
              onChange={(e) => form.setData('meta_keywords', e.target.value)} />
          </section>
        </div>
      </form>
    </>
  );
}

Form.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Categories / Edit">{page}</AdminLayout>
);

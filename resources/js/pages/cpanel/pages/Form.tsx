import { Head, useForm, usePage } from '@inertiajs/react';
import { useRef } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import { TextField } from '@/components/TextField';
import { Button } from '@/components/Button';
import { RichText } from '@/components/RichText';
import { CustomFieldsBuilder } from '@/components/admin/CustomFieldsBuilder';
import type { CustomFieldsMap } from '@/components/admin/CustomFieldsBuilder';
import { useLfmPicker } from '@/lib/lfm';
import type { SharedProps } from '@/lib/types';
import type { FormEvent, ReactElement } from 'react';

interface PageEntity {
  id: number;
  title: string;
  slug: string;
  content: string;
  author_id: number | null;
  meta_keywords: string;
  meta_description: string;
  canonical_url: string;
  meta_noindex: boolean;
  status: number;
  template: string;
  updated_at: string;
  custom_fields: CustomFieldsMap;
}
interface TemplateOption { value: string; label: string }
interface AuthorOption { id: number; username: string }
interface CategoryOption { category_id: number; title: string }
interface FormProps {
  entity: PageEntity | null;
  templates: TemplateOption[];
  authors: AuthorOption[];
  categories_list: CategoryOption[];
  translation_links: Record<string, string>;
}

const BASE = '/agentic-cms-laravel-admin/pages';

export default function Form({ entity, templates, authors, categories_list, translation_links }: FormProps) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const { locale } = usePage<SharedProps>().props;

  const form = useForm({
    title: entity?.title ?? '',
    slug: entity?.slug ?? '',
    content: entity?.content ?? '',
    author_id: entity?.author_id ?? authors[0]?.id ?? '',
    meta_keywords: entity?.meta_keywords ?? '',
    meta_description: entity?.meta_description ?? '',
    canonical_url: entity?.canonical_url ?? '',
    meta_noindex: entity?.meta_noindex ?? false,
    status: entity?.status ?? 1,
    template: entity?.template ?? templates[0]?.value ?? '',
    updated_at: entity?.updated_at ?? '',
    custom_fields: (entity?.custom_fields ?? {}) as CustomFieldsMap,
  });

  const insertRef = useRef<((url: string) => void) | null>(null);
  const editorImage = useLfmPicker((url) => insertRef.current?.(url));
  const handlePickImage = (insert: (url: string) => void) => {
    insertRef.current = insert;
    editorImage.open('Images');
  };

  const submit = (e: FormEvent) => {
    e.preventDefault();
    // useForm sends every key; ValidatePageData marks updated_at
    // sometimes|required, so an empty string would fail on a new page.
    form.transform((data) => {
      const d: Record<string, unknown> = { ...data };
      if (!d.updated_at) delete d.updated_at;
      return d;
    });
    if (entity) form.put(`${BASE}/${entity.id}/update`);
    else form.post(`${BASE}/new`);
  };

  const heading = entity
    ? tr('cpanel/pages.edit_page_headline', 'Edit page')
    : tr('cpanel/pages.add_new_page', 'New page');

  return (
    <>
      <Head title={heading} />
      <form onSubmit={submit}>
        <div className="mb-5 flex items-center gap-4">
          <h1 className="text-[22px] font-semibold tracking-tight">{heading}</h1>
          <div className="ml-auto flex items-center gap-2.5">
            {entity && (
              <Button href={`${BASE}/${entity.id}/revisions/${locale.current}`} variant="outline" size="md">
                {tr('cpanel/revisions.revisions_link', 'Revisions')}
              </Button>
            )}
            {Object.entries(translation_links).length > 0 && (
              <div className="inline-flex gap-1 rounded-[10px] admin-bevel p-1">
                <span className="rounded-md bg-primary px-2.5 py-1 text-xs font-semibold uppercase text-primary-contrast">
                  {locale.current}
                </span>
                {Object.entries(translation_links).map(([title, href]) => (
                  <a key={href} href={`/${href}`} className="rounded-md px-2.5 py-1 text-xs font-semibold uppercase text-muted">
                    {title.slice(0, 2)}
                  </a>
                ))}
              </div>
            )}
            <Button href={BASE} variant="outline" size="md">{tr('cpanel/pages.cancel', 'Cancel')}</Button>
            <Button type="submit" variant="primary" size="md" loading={form.processing} data-testid="page-submit">
              {tr('cpanel/pages.save', 'Save')}
            </Button>
          </div>
        </div>

        <div className="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_320px]">
          <section className="admin-card flex flex-col gap-4 p-[18px]">
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
              <TextField name="title" label={tr('cpanel/pages.title', 'Title')} required
                data-testid="page-title" value={form.data.title} error={form.errors.title}
                onChange={(e) => form.setData('title', e.target.value)} />
              <TextField name="slug" label={tr('cpanel/pages.slug', 'Slug')} required
                data-testid="page-slug" value={form.data.slug} error={form.errors.slug}
                onChange={(e) => form.setData('slug', e.target.value)} />
            </div>

            <div>
              <label className="mb-1.5 block text-xs font-semibold text-fg">
                {tr('cpanel/pages.content', 'Content')}
              </label>
              <RichText id="content" name="content" height={340}
                value={form.data.content} onChange={(html) => form.setData('content', html)}
                onPickImage={handlePickImage} />
              {form.errors.content && <p className="mt-1.5 text-xs text-error">{form.errors.content}</p>}
            </div>

            <div className="mt-2 border-t admin-sep pt-4">
              <h3 className="mb-3 text-[13px] font-semibold">{tr('cpanel/seo.seo_headline', 'SEO')}</h3>
              <div className="flex flex-col gap-4">
                <TextField name="meta_keywords" label={tr('cpanel/seo.meta_keywords_headline', 'Meta keywords')}
                  value={form.data.meta_keywords} error={form.errors.meta_keywords}
                  onChange={(e) => form.setData('meta_keywords', e.target.value)} />
                <TextField name="meta_description" label={tr('cpanel/seo.meta_description_headline', 'Meta description')}
                  value={form.data.meta_description} error={form.errors.meta_description}
                  onChange={(e) => form.setData('meta_description', e.target.value)} />
                <TextField name="canonical_url" label={tr('cpanel/seo.canonical_headline', 'Canonical URL')}
                  value={form.data.canonical_url} error={form.errors.canonical_url}
                  onChange={(e) => form.setData('canonical_url', e.target.value)} />
                <label className="flex cursor-pointer items-center gap-2.5 text-sm text-muted">
                  <input type="checkbox" name="meta_noindex" checked={form.data.meta_noindex}
                    onChange={(e) => form.setData('meta_noindex', e.target.checked)} />
                  {tr('cpanel/seo.noindex_headline', 'Discourage search engines (noindex)')}
                </label>
              </div>
            </div>

            <CustomFieldsBuilder
              value={form.data.custom_fields}
              categories={categories_list}
              onChange={(next) => form.setData('custom_fields', next)} />
          </section>

          <section className="admin-card flex flex-col gap-4 p-[18px]">
            <div>
              <label htmlFor="author_id" className="mb-1.5 block text-xs font-semibold text-fg">
                {tr('cpanel/pages.author', 'Author')}
              </label>
              <select id="author_id" name="author_id" aria-label="author" className="field-input w-full"
                value={form.data.author_id}
                onChange={(e) => form.setData('author_id', Number(e.target.value))}>
                {authors.map((a) => (
                  <option key={a.id} value={a.id}>{a.username}</option>
                ))}
              </select>
              {form.errors.author_id && <p className="mt-1.5 text-xs text-error">{form.errors.author_id}</p>}
            </div>

            <div>
              <label htmlFor="status" className="mb-1.5 block text-xs font-semibold text-fg">
                {tr('cpanel/pages.status', 'Status')}
              </label>
              <select id="status" name="status" aria-label="status" className="field-input w-full"
                value={form.data.status}
                onChange={(e) => form.setData('status', Number(e.target.value))}>
                <option value={1}>{tr('cpanel/pages.status_published', 'Published')}</option>
                <option value={0}>{tr('cpanel/pages.status_private', 'Private')}</option>
              </select>
            </div>

            <div>
              <label htmlFor="template" className="mb-1.5 block text-xs font-semibold text-fg">
                {tr('cpanel/pages.page_template', 'Template')}
              </label>
              <select id="template" name="template" aria-label="template" className="field-input w-full"
                value={form.data.template}
                onChange={(e) => form.setData('template', e.target.value)}>
                {templates.map((tp) => (
                  <option key={tp.value} value={tp.value}>{tp.label}</option>
                ))}
              </select>
              {form.errors.template && <p className="mt-1.5 text-xs text-error">{form.errors.template}</p>}
            </div>

            <TextField name="updated_at" label={tr('cpanel/pages.publish_date', 'Publish date')}
              placeholder="YYYY-MM-DD HH:MM:SS"
              value={form.data.updated_at} error={form.errors.updated_at}
              onChange={(e) => form.setData('updated_at', e.target.value)} />
          </section>
        </div>
      </form>
    </>
  );
}

Form.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Pages / Edit">{page}</AdminLayout>
);

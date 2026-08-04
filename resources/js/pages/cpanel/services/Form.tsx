import { Head, useForm, usePage } from '@inertiajs/react';
import { useRef } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import { TextField } from '@/components/TextField';
import { Button } from '@/components/Button';
import { RichText } from '@/components/RichText';
import { MediaField } from '@/components/MediaField';
import { useLfmPicker } from '@/lib/lfm';
import type { SharedProps } from '@/lib/types';
import type { FormEvent, ReactElement } from 'react';

interface ServiceEntity {
  id: number;
  title: string;
  slug: string;
  icon: string;
  excerpt: string;
  content: string;
  thumbnail: string;
  meta_keywords: string;
  meta_description: string;
  canonical_url: string;
  meta_noindex: boolean;
  sort_order: number;
  status: number;
}
interface FormProps {
  entity: ServiceEntity | null;
  translation_links: Record<string, string>;
}

const BASE = '/agentic-cms-laravel-admin/services';

export default function Form({ entity, translation_links }: FormProps) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const { locale } = usePage<SharedProps>().props;

  const form = useForm({
    title: entity?.title ?? '',
    slug: entity?.slug ?? '',
    icon: entity?.icon ?? '',
    excerpt: entity?.excerpt ?? '',
    content: entity?.content ?? '',
    thumbnail: entity?.thumbnail ?? '',
    meta_keywords: entity?.meta_keywords ?? '',
    meta_description: entity?.meta_description ?? '',
    canonical_url: entity?.canonical_url ?? '',
    meta_noindex: entity?.meta_noindex ?? false,
    sort_order: entity?.sort_order ?? 0,
    status: entity?.status ?? 1,
  });

  const insertRef = useRef<((url: string) => void) | null>(null);
  const editorImage = useLfmPicker((url) => insertRef.current?.(url));
  const handlePickImage = (insert: (url: string) => void) => {
    insertRef.current = insert;
    editorImage.open('Images');
  };

  const submit = (e: FormEvent) => {
    e.preventDefault();
    if (entity) form.put(`${BASE}/${entity.id}/update`);
    else form.post(`${BASE}/new`);
  };

  const heading = entity
    ? tr('cpanel/services.edit_headline', 'Edit service')
    : tr('cpanel/services.add_new_service', 'New service');

  return (
    <>
      <Head title={heading} />
      <form onSubmit={submit}>
        <div className="mb-5 flex items-center gap-4">
          <h1 className="text-[22px] font-semibold tracking-tight">{heading}</h1>
          <div className="ml-auto flex items-center gap-2.5">
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
            <Button href={BASE} variant="outline" size="md">{tr('cpanel/services.cancel', 'Cancel')}</Button>
            <Button type="submit" variant="primary" size="md" loading={form.processing} data-testid="service-submit">
              {tr('cpanel/services.save', 'Save')}
            </Button>
          </div>
        </div>

        <div className="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_320px]">
          <section className="admin-card flex flex-col gap-4 p-[18px]">
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
              <TextField name="title" label={tr('cpanel/services.title', 'Title')} required
                data-testid="service-title" value={form.data.title} error={form.errors.title}
                onChange={(e) => form.setData('title', e.target.value)} />
              <TextField name="slug" label={tr('cpanel/services.slug', 'Slug')} required
                data-testid="service-slug" value={form.data.slug} error={form.errors.slug}
                onChange={(e) => form.setData('slug', e.target.value)} />
            </div>

            <TextField name="excerpt" label={tr('cpanel/services.excerpt', 'Excerpt')}
              value={form.data.excerpt} error={form.errors.excerpt}
              onChange={(e) => form.setData('excerpt', e.target.value)} />

            <div>
              <label className="mb-1.5 block text-xs font-semibold text-fg">
                {tr('cpanel/services.content', 'Content')}
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
          </section>

          <section className="admin-card flex flex-col gap-4 p-[18px]">
            <TextField name="icon" label={tr('cpanel/services.icon', 'Icon')}
              placeholder={tr('cpanel/services.icon_hint', 'CSS class or name')}
              value={form.data.icon} error={form.errors.icon}
              onChange={(e) => form.setData('icon', e.target.value)} />

            <div>
              <label htmlFor="status" className="mb-1.5 block text-xs font-semibold text-fg">
                {tr('cpanel/services.status', 'Status')}
              </label>
              <select id="status" name="status" aria-label="status" className="field-input w-full"
                value={form.data.status}
                onChange={(e) => form.setData('status', Number(e.target.value))}>
                <option value={1}>{tr('cpanel/services.status_published', 'Published')}</option>
                <option value={0}>{tr('cpanel/services.status_private', 'Private')}</option>
              </select>
            </div>

            <TextField name="sort_order" label={tr('cpanel/services.sort_order', 'Sort order')} type="number"
              value={String(form.data.sort_order)} error={form.errors.sort_order}
              onChange={(e) => form.setData('sort_order', Number(e.target.value))} />

            <MediaField label={tr('cpanel/services.thumbnail', 'Thumbnail')}
              value={form.data.thumbnail} onChange={(url) => form.setData('thumbnail', url)} />
          </section>
        </div>
      </form>
    </>
  );
}

Form.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Services / Edit">{page}</AdminLayout>
);

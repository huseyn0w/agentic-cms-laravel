import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import { TextField } from '@/components/TextField';
import { Button } from '@/components/Button';
import type { FormEvent, ReactElement } from 'react';

interface Term {
  title: string;
  slug: string;
}
interface MenuItem {
  title: string;
  slug: string;
  type: string;
  children?: MenuItem[];
}
interface MenuEntity {
  id: number;
  title: string;
  slug: string;
  items: MenuItem[];
}
interface FormProps {
  entity: MenuEntity | null;
  terms_list: { posts: Term[]; pages: Term[]; categories: Term[] };
  translation_links?: Record<string, string>;
}

const BASE = '/agentic-cms-laravel-admin/menus';

// [terms key, source data-type, i18n label, fallback]
const SOURCES = [
  ['pages', 'pages', 'cpanel/menus.pages', 'Pages'],
  ['posts', 'posts', 'cpanel/menus.posts', 'Posts'],
  ['categories', 'categories', 'cpanel/menus.categories', 'Categories'],
] as const;

export default function Form({ entity, terms_list }: FormProps) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));

  const form = useForm<{ title: string; slug: string; content: string }>({
    title: entity?.title ?? '',
    slug: entity?.slug ?? '',
    content: '',
  });

  const [items, setItems] = useState<MenuItem[]>(entity?.items ?? []);
  const [selected, setSelected] = useState<Record<string, string[]>>({ pages: [], posts: [], categories: [] });
  const [linkLabel, setLinkLabel] = useState('');
  const [linkUrl, setLinkUrl] = useState('');
  const [announce, setAnnounce] = useState('');

  const onSelectChange = (key: string, e: React.ChangeEvent<HTMLSelectElement>) =>
    setSelected((s) => ({ ...s, [key]: Array.from(e.target.selectedOptions).map((o) => o.value) }));

  const addToMenu = () => {
    const added: MenuItem[] = [];
    for (const [key, type] of SOURCES.map(([k, ty]) => [k, ty] as const)) {
      for (const slug of selected[key]) {
        const term = terms_list[key as keyof typeof terms_list].find((t) => t.slug === slug);
        if (term) added.push({ title: term.title, slug: term.slug || '/', type });
      }
    }
    if (linkLabel && linkUrl) added.push({ title: linkLabel, slug: linkUrl, type: 'custom_link' });
    if (added.length === 0) return;
    setItems((prev) => [...prev, ...added]);
    setSelected({ pages: [], posts: [], categories: [] });
    setLinkLabel('');
    setLinkUrl('');
  };

  const move = (index: number, dir: -1 | 1) => {
    setItems((prev) => {
      const target = index + dir;
      if (target < 0 || target >= prev.length) return prev;
      const next = [...prev];
      [next[index], next[target]] = [next[target], next[index]];
      setAnnounce(`${next[target].title} ${tr('cpanel/menus.reorder_moved', 'moved to position')} ${target + 1}`);
      return next;
    });
  };

  const remove = (index: number) => setItems((prev) => prev.filter((_, i) => i !== index));

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.transform((data) => ({ ...data, content: JSON.stringify(items) }));
    if (entity) form.put(`${BASE}/${entity.id}/update`, { preserveScroll: true });
    else form.post(`${BASE}/new`);
  };

  const heading = entity
    ? tr('cpanel/menus.edit_menu_headline', 'Edit menu')
    : tr('cpanel/menus.new_menu_headline', 'New menu');

  return (
    <>
      <Head title={heading} />
      <form onSubmit={submit} className="mx-auto max-w-6xl">
        <div className="mb-5 flex items-center gap-4">
          <h1 className="text-[22px] font-semibold tracking-tight">{heading}</h1>
          <div className="ml-auto flex items-center gap-2.5">
            <Button href={BASE} variant="outline" size="md">{tr('cpanel/menus.cancel', 'Cancel')}</Button>
            <Button type="submit" variant="primary" size="md" loading={form.processing} data-testid="menu-submit">
              {entity ? tr('cpanel/menus.update_menu', 'Update menu') : tr('cpanel/menus.create_menu', 'Create menu')}
            </Button>
          </div>
        </div>

        <div className="grid grid-cols-1 gap-4 lg:grid-cols-[340px_1fr]">
          {/* Source panel */}
          <section className="admin-card flex flex-col gap-4 p-[18px]">
            <TextField name="title" label={tr('cpanel/menus.menu_name', 'Menu name')} required
              data-testid="menu-title" value={form.data.title} error={form.errors.title}
              onChange={(e) => form.setData('title', e.target.value)} />
            <TextField name="slug" label={tr('cpanel/menus.menu_slug', 'Menu slug')} required
              data-testid="menu-slug" value={form.data.slug} error={form.errors.slug}
              onChange={(e) => form.setData('slug', e.target.value)} />

            {SOURCES.map(([key, , labelKey, fallback]) => (
              <div key={key} className="flex flex-col gap-1.5">
                <label htmlFor={`source-${key}`} className="text-[12px] font-semibold text-muted">
                  {tr(labelKey, fallback)}
                </label>
                <select id={`source-${key}`} aria-label={`source-${key}`} multiple size={4}
                  className="field-input w-full" value={selected[key]}
                  onChange={(e) => onSelectChange(key, e)}>
                  {terms_list[key as keyof typeof terms_list].map((term) => (
                    <option key={`${term.slug}-${term.title}`} value={term.slug}>{term.title}</option>
                  ))}
                </select>
              </div>
            ))}

            <div className="flex flex-col gap-2 border-t admin-sep pt-3">
              <span className="text-[12px] font-semibold text-muted">{tr('cpanel/menus.custom_link', 'Custom Link')}</span>
              <TextField name="link_label" label={tr('cpanel/menus.custom_link_label', 'Label')}
                data-testid="link-label" value={linkLabel} onChange={(e) => setLinkLabel(e.target.value)} />
              <TextField name="link_url" label={tr('cpanel/menus.custom_link_url', 'URL')}
                data-testid="link-url" value={linkUrl} onChange={(e) => setLinkUrl(e.target.value)} />
            </div>

            <Button type="button" variant="outline" size="md" onClick={addToMenu} data-testid="add-to-menu">
              {tr('cpanel/menus.add_to_menu', 'Add to menu')}
            </Button>
          </section>

          {/* Builder canvas */}
          <section className="admin-card flex flex-col gap-3 p-[18px]">
            <h2 className="text-[13px] font-semibold">{tr('cpanel/menus.list_headline', 'Menu')}</h2>
            <div data-testid="menu-canvas"
              className="min-h-[200px] rounded-md border border-dashed admin-sep bg-surface-2 p-3">
              {items.length === 0 && (
                <p className="py-8 text-center text-[13px] text-faint">{tr('cpanel/menus.not_found', 'No items yet')}</p>
              )}
              <ul className="flex flex-col gap-2" role="list">
                {items.map((item, i) => (
                  <li key={`${item.slug}-${i}`} data-testid="menu-item"
                    className="flex items-center gap-2 rounded-md bg-surface px-3 py-2 admin-bevel">
                    <span className="flex-1 text-[13px]">
                      <span className="font-semibold">{item.title}</span>
                      <span className="ml-2 text-[11px] uppercase tracking-wide text-faint">{item.type}</span>
                    </span>
                    <button type="button" aria-label="move-up" onClick={() => move(i, -1)}
                      className="px-1.5 text-muted hover:text-fg disabled:opacity-30" disabled={i === 0}>↑</button>
                    <button type="button" aria-label="move-down" onClick={() => move(i, 1)}
                      className="px-1.5 text-muted hover:text-fg disabled:opacity-30" disabled={i === items.length - 1}>↓</button>
                    <button type="button" aria-label="remove-item" onClick={() => remove(i)}
                      className="px-1.5 text-muted hover:text-error">✕</button>
                  </li>
                ))}
              </ul>
            </div>
            <div data-testid="menu-reorder-live" role="status" aria-live="polite" className="sr-only">{announce}</div>
          </section>
        </div>
      </form>
    </>
  );
}

Form.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Menus / Edit">{page}</AdminLayout>
);

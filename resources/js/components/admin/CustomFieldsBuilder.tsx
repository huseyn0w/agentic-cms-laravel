import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { RichText } from '@/components/RichText';
import { MediaField } from '@/components/MediaField';
import { RepeaterField } from '@/components/admin/RepeaterField';
import type { RepeaterRows } from '@/components/admin/RepeaterField';

export type CustomFieldType = 'text' | 'textarea' | 'image' | 'link' | 'category' | 'repeater';
export interface LinkValue {
  label: string;
  url: string;
  target: string; // "0" | "1"
}
/**
 * JSON-serializable field value. Deliberately non-recursive (the repeater's
 * nested rows use `Record<string, unknown>`/`unknown[]`) so Inertia's useForm
 * type mapping doesn't blow its instantiation depth.
 */
export type CustomFieldValue =
  | string
  | number
  | boolean
  | null
  | LinkValue
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  | Record<string, any>
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  | any[];
export interface CustomFieldEntry {
  type: CustomFieldType;
  admin_label: string;
  value: CustomFieldValue;
}
export type CustomFieldsMap = Record<string, CustomFieldEntry>;
export interface CustomField extends CustomFieldEntry {
  key: string;
}
interface CategoryOption {
  category_id: number;
  title: string;
}
interface CustomFieldsBuilderProps {
  /** The stored associative structure: { [key]: { type, admin_label, value } }. */
  value: CustomFieldsMap;
  onChange: (next: CustomFieldsMap) => void;
  categories: CategoryOption[];
}

/** Kebab-case slug used as the field key (mirrors the legacy url_slug()). */
export function slugify(input: string): string {
  return input
    .toString()
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

const ADDABLE: CustomFieldType[] = ['text', 'textarea', 'image', 'link', 'category', 'repeater'];

/**
 * React port of the page custom-fields builder. Fields are stored as an
 * associative object keyed by a slug (`get_field(key, ...)` on the theme side).
 * This stage edits the five simple types; a `repeater` value loaded from an
 * existing page is preserved untouched so nothing is lost on save.
 */
export function CustomFieldsBuilder({ value, onChange, categories }: CustomFieldsBuilderProps) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));

  // Work with an ordered list internally; serialize back to the keyed object.
  const fields = useMemo<CustomField[]>(
    () => Object.entries(value ?? {}).map(([key, f]) => ({ key, ...f })),
    [value],
  );

  const [addType, setAddType] = useState<CustomFieldType>('text');
  const [addLabel, setAddLabel] = useState('');
  const [addName, setAddName] = useState('');

  const emit = (list: CustomField[]) => {
    const next: CustomFieldsMap = {};
    for (const f of list) {
      next[f.key] = { type: f.type, admin_label: f.admin_label, value: f.value };
    }
    onChange(next);
  };

  const defaultValueFor = (type: CustomFieldType): CustomFieldValue => {
    if (type === 'link') return { label: '', url: '', target: '0' };
    if (type === 'repeater') return {};
    return '';
  };

  const addField = () => {
    const key = slugify(addName || addLabel);
    if (!key || !addLabel) return;
    if (fields.some((f) => f.key === key)) return; // keys are unique
    emit([...fields, { key, type: addType, admin_label: addLabel, value: defaultValueFor(addType) }]);
    setAddLabel('');
    setAddName('');
  };

  const updateValue = (key: string, v: CustomFieldValue) =>
    emit(fields.map((f) => (f.key === key ? { ...f, value: v } : f)));

  const removeField = (key: string) => emit(fields.filter((f) => f.key !== key));

  return (
    <div className="mt-2 border-t admin-sep pt-4">
      <h3 className="mb-3 text-[13px] font-semibold">{tr('cpanel/custom-fields.headline', 'Custom fields')}</h3>

      <div className="flex flex-col gap-3">
        {fields.map((f) => (
          <div key={f.key} className="rounded-[10px] admin-bevel p-3" data-testid={`cf-${f.key}`}>
            {f.type === 'repeater' ? (
              <RepeaterField
                fieldKey={f.key}
                admin_label={f.admin_label}
                value={(f.value ?? {}) as RepeaterRows}
                onChange={(rows) => updateValue(f.key, rows)}
                onDelete={() => removeField(f.key)}
                tr={tr}
              />
            ) : (
              <>
            <div className="mb-2 flex items-center gap-2">
              <span className="text-xs font-semibold text-fg">{f.admin_label}</span>
              <span className="rounded bg-surface-2 px-1.5 py-0.5 text-[10px] uppercase text-faint">{f.type}</span>
              <code className="text-[10px] text-faint">{f.key}</code>
              <button type="button" onClick={() => removeField(f.key)}
                className="ml-auto text-xs text-muted hover:text-error" data-testid={`cf-remove-${f.key}`}>
                {tr('cpanel/custom-fields.remove', 'Remove')}
              </button>
            </div>

            {f.type === 'text' && (
              <input type="text" className="field-input w-full" value={String(f.value ?? '')}
                aria-label={`cf-value-${f.key}`}
                onChange={(e) => updateValue(f.key, e.target.value)} />
            )}

            {f.type === 'textarea' && (
              <RichText id={`cf_${f.key}`} name={`cf_${f.key}`} height={160}
                value={String(f.value ?? '')} onChange={(html) => updateValue(f.key, html)} />
            )}

            {f.type === 'image' && (
              <MediaField label={tr('cpanel/custom-fields.image_label', 'Image')}
                value={String(f.value ?? '')} onChange={(url) => updateValue(f.key, url)} />
            )}

            {f.type === 'category' && (
              <select className="field-input w-full" aria-label={`cf-value-${f.key}`}
                value={String((f.value as string | number) ?? '')}
                onChange={(e) => updateValue(f.key, e.target.value)}>
                <option value="">—</option>
                {categories.map((c) => (
                  <option key={c.category_id} value={c.category_id}>{c.title}</option>
                ))}
              </select>
            )}

            {f.type === 'link' && (
              <LinkFieldEditor value={f.value as LinkValue} onChange={(v) => updateValue(f.key, v)}
                fieldKey={f.key} tr={tr} />
            )}
              </>
            )}
          </div>
        ))}
      </div>

      <div className="mt-3 flex flex-wrap items-end gap-2 rounded-[10px] admin-bevel p-3">
        <label className="flex flex-col gap-1 text-xs text-muted">
          {tr('cpanel/custom-fields.field_type', 'Type')}
          <select className="field-input" aria-label="cf-add-type" value={addType}
            onChange={(e) => setAddType(e.target.value as Exclude<CustomFieldType, 'repeater'>)}>
            {ADDABLE.map((tp) => (
              <option key={tp} value={tp}>{tr(`cpanel/custom-fields.type_${tp}`, tp)}</option>
            ))}
          </select>
        </label>
        <label className="flex flex-col gap-1 text-xs text-muted">
          {tr('cpanel/custom-fields.text_label', 'Label')}
          <input type="text" className="field-input" aria-label="cf-add-label"
            value={addLabel} onChange={(e) => setAddLabel(e.target.value)} />
        </label>
        <label className="flex flex-col gap-1 text-xs text-muted">
          {tr('cpanel/custom-fields.text_name', 'Key')}
          <input type="text" className="field-input" aria-label="cf-add-name" placeholder={slugify(addLabel)}
            value={addName} onChange={(e) => setAddName(e.target.value)} />
        </label>
        <button type="button" onClick={addField} data-testid="cf-add"
          className="h-9 rounded-[10px] bg-primary px-3.5 text-[13px] font-semibold text-primary-contrast">
          + {tr('cpanel/custom-fields.add_field', 'Add field')}
        </button>
      </div>
    </div>
  );
}

export function LinkFieldEditor({
  value, onChange, fieldKey, tr,
}: {
  value: LinkValue;
  onChange: (v: LinkValue) => void;
  fieldKey: string;
  tr: (k: string, f: string) => string;
}) {
  const v = value ?? { label: '', url: '', target: '0' };
  return (
    <div className="flex flex-col gap-2">
      <input type="text" className="field-input w-full" aria-label={`cf-link-label-${fieldKey}`}
        placeholder={tr('cpanel/custom-fields.link_label', 'Label')}
        value={v.label} onChange={(e) => onChange({ ...v, label: e.target.value })} />
      <input type="text" className="field-input w-full" aria-label={`cf-link-url-${fieldKey}`}
        placeholder={tr('cpanel/custom-fields.link_url', 'URL')}
        value={v.url} onChange={(e) => onChange({ ...v, url: e.target.value })} />
      <label className="flex items-center gap-2 text-sm text-muted">
        <input type="checkbox" aria-label={`cf-link-target-${fieldKey}`}
          checked={v.target === '1'}
          onChange={(e) => onChange({ ...v, target: e.target.checked ? '1' : '0' })} />
        {tr('cpanel/custom-fields.open_in_new_tab', 'Open in a new tab')}
      </label>
    </div>
  );
}

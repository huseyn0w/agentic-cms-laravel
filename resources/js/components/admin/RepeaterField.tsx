import { useState } from 'react';
import { RichText } from '@/components/RichText';
import { MediaField } from '@/components/MediaField';
import { LinkFieldEditor, slugify } from '@/components/admin/CustomFieldsBuilder';
import type { LinkValue } from '@/components/admin/CustomFieldsBuilder';

export type RepeaterItemType = 'text' | 'textarea' | 'image' | 'link';
export interface RepeaterItem {
  type: RepeaterItemType;
  admin_label: string;
  value: string | LinkValue;
}
export type RepeaterRow = Record<string, RepeaterItem>;
export type RepeaterRows = Record<string, RepeaterRow>;
type Row = RepeaterRow;
type Rows = RepeaterRows;

interface RepeaterFieldProps {
  fieldKey: string;
  admin_label: string;
  value: Rows;
  onChange: (next: Rows) => void;
  onDelete: () => void;
  tr: (k: string, f: string) => string;
}

const SUB_TYPES: RepeaterItemType[] = ['text', 'textarea', 'image', 'link'];

/** Next `row-N` key one past the highest existing numeric suffix. */
function nextRowKey(rows: Rows): string {
  const max = Object.keys(rows).reduce((m, k) => {
    const n = Number(k.replace('row-', ''));
    return Number.isFinite(n) && n > m ? n : m;
  }, -1);
  return `row-${max + 1}`;
}

function emptyValue(type: RepeaterItemType): string | LinkValue {
  return type === 'link' ? { label: '', url: '', target: '0' } : '';
}

/**
 * Inline editor for a repeater custom field. The stored shape is
 * `{ 'row-0': { itemKey: {type,admin_label,value} }, ... }`; every row shares
 * the same item schema, which is derived from the first row. Sub-items are
 * text / textarea / image / link (categories and nested repeaters are not
 * repeatable, matching the legacy builder).
 */
export function RepeaterField({ fieldKey, admin_label, value, onChange, onDelete, tr }: RepeaterFieldProps) {
  const rows = value ?? {};
  const rowKeys = Object.keys(rows);
  const schema = rowKeys.length > 0
    ? Object.entries(rows[rowKeys[0]]).map(([itemKey, item]) => ({ itemKey, type: item.type, admin_label: item.admin_label }))
    : [];

  const [addType, setAddType] = useState<RepeaterItemType>('text');
  const [addLabel, setAddLabel] = useState('');
  const [addName, setAddName] = useState('');

  const addSubField = () => {
    const itemKey = slugify(addName || addLabel);
    if (!itemKey || !addLabel) return;
    if (schema.some((s) => s.itemKey === itemKey)) return;
    const item: RepeaterItem = { type: addType, admin_label: addLabel, value: emptyValue(addType) };

    let next: Rows;
    if (rowKeys.length === 0) {
      next = { 'row-0': { [itemKey]: item } };
    } else {
      next = {};
      for (const [rk, row] of Object.entries(rows)) {
        next[rk] = { ...row, [itemKey]: { ...item } };
      }
    }
    onChange(next);
    setAddLabel('');
    setAddName('');
  };

  const removeSubField = (itemKey: string) => {
    const next: Rows = {};
    for (const [rk, row] of Object.entries(rows)) {
      const { [itemKey]: _drop, ...rest } = row;
      next[rk] = rest;
    }
    onChange(next);
  };

  const addRow = () => {
    if (schema.length === 0) return;
    const row: Row = {};
    for (const s of schema) {
      row[s.itemKey] = { type: s.type, admin_label: s.admin_label, value: emptyValue(s.type) };
    }
    onChange({ ...rows, [nextRowKey(rows)]: row });
  };

  const removeRow = (rowKey: string) => {
    const { [rowKey]: _drop, ...rest } = rows;
    onChange(rest);
  };

  const updateItem = (rowKey: string, itemKey: string, v: string | LinkValue) => {
    onChange({
      ...rows,
      [rowKey]: { ...rows[rowKey], [itemKey]: { ...rows[rowKey][itemKey], value: v } },
    });
  };

  return (
    <div className="flex flex-col gap-3" data-testid={`cf-repeater-${fieldKey}`}>
      <div className="flex items-center gap-2">
        <span className="text-xs font-semibold text-fg">{admin_label}</span>
        <span className="text-[11px] text-faint">
          {rowKeys.length} {tr('cpanel/custom-fields.rows', 'rows')}
        </span>
        <button type="button" onClick={onDelete} className="ml-auto text-xs text-muted hover:text-error"
          data-testid={`cf-repeater-delete-${fieldKey}`}>
          {tr('cpanel/custom-fields.delete_group', 'Delete group')}
        </button>
      </div>

      {/* Field schema editor */}
      <div className="flex flex-wrap items-end gap-2 rounded-md admin-bevel p-2.5">
        {schema.map((s) => (
          <span key={s.itemKey} className="inline-flex items-center gap-1 rounded bg-surface-2 px-2 py-1 text-[11px]">
            {s.admin_label} <code className="text-faint">{s.type}</code>
            <button type="button" onClick={() => removeSubField(s.itemKey)}
              className="text-muted hover:text-error" data-testid={`cf-repeater-${fieldKey}-delfield-${s.itemKey}`}>×</button>
          </span>
        ))}
        <select className="field-input h-8 text-xs" aria-label={`cf-repeater-${fieldKey}-addtype`}
          value={addType} onChange={(e) => setAddType(e.target.value as RepeaterItemType)}>
          {SUB_TYPES.map((tp) => (
            <option key={tp} value={tp}>{tr(`cpanel/custom-fields.type_${tp}`, tp)}</option>
          ))}
        </select>
        <input type="text" className="field-input h-8 text-xs" aria-label={`cf-repeater-${fieldKey}-addlabel`}
          placeholder={tr('cpanel/custom-fields.text_label', 'Label')}
          value={addLabel} onChange={(e) => setAddLabel(e.target.value)} />
        <input type="text" className="field-input h-8 text-xs" aria-label={`cf-repeater-${fieldKey}-addname`}
          placeholder={slugify(addLabel) || tr('cpanel/custom-fields.text_name', 'Key')}
          value={addName} onChange={(e) => setAddName(e.target.value)} />
        <button type="button" onClick={addSubField} data-testid={`cf-repeater-${fieldKey}-addfield`}
          className="h-8 rounded-md bg-surface-2 px-2.5 text-xs font-semibold text-fg">
          {tr('cpanel/custom-fields.add_field', 'Add field')}
        </button>
      </div>

      {/* Rows */}
      {rowKeys.map((rk, i) => (
        <div key={rk} className="rounded-md admin-bevel p-2.5" data-testid={`cf-repeater-${fieldKey}-${rk}`}>
          <div className="mb-2 flex items-center">
            <span className="text-[11px] uppercase text-faint">{tr('cpanel/custom-fields.row', 'Row')} {i + 1}</span>
            <button type="button" onClick={() => removeRow(rk)} className="ml-auto text-xs text-muted hover:text-error"
              data-testid={`cf-repeater-${fieldKey}-removerow-${rk}`}>
              {tr('cpanel/custom-fields.delete_row', 'Remove row')}
            </button>
          </div>
          <div className="flex flex-col gap-2.5">
            {Object.entries(rows[rk]).map(([itemKey, item]) => (
              <div key={itemKey}>
                <label className="mb-1 block text-[11px] font-medium text-muted">{item.admin_label}</label>
                {item.type === 'text' && (
                  <input type="text" className="field-input w-full"
                    aria-label={`cf-repeater-${fieldKey}-${rk}-${itemKey}`}
                    value={String(item.value ?? '')}
                    onChange={(e) => updateItem(rk, itemKey, e.target.value)} />
                )}
                {item.type === 'textarea' && (
                  <RichText id={`rep_${fieldKey}_${rk}_${itemKey}`} name={`rep_${fieldKey}_${rk}_${itemKey}`} height={140}
                    value={String(item.value ?? '')} onChange={(html) => updateItem(rk, itemKey, html)} />
                )}
                {item.type === 'image' && (
                  <MediaField label={tr('cpanel/custom-fields.image_label', 'Image')}
                    value={String(item.value ?? '')} onChange={(url) => updateItem(rk, itemKey, url)} />
                )}
                {item.type === 'link' && (
                  <LinkFieldEditor value={item.value as LinkValue} fieldKey={`${fieldKey}-${rk}-${itemKey}`} tr={tr}
                    onChange={(v) => updateItem(rk, itemKey, v)} />
                )}
              </div>
            ))}
          </div>
        </div>
      ))}

      {schema.length > 0 && (
        <button type="button" onClick={addRow} data-testid={`cf-repeater-${fieldKey}-addrow`}
          className="self-start rounded-md bg-surface-2 px-3 py-1.5 text-xs font-semibold text-fg">
          + {tr('cpanel/custom-fields.add_row', 'Add row')}
        </button>
      )}
    </div>
  );
}

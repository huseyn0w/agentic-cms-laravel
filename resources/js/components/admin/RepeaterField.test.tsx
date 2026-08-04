import { render, screen, fireEvent } from '@testing-library/react';
import { useState } from 'react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
vi.mock('@/components/RichText', () => ({
  RichText: ({ id, value, onChange }: any) => (
    <textarea data-testid={`richtext-${id}`} value={value} onChange={(e) => onChange(e.target.value)} />
  ),
}));
vi.mock('@/components/MediaField', () => ({
  MediaField: ({ value, onChange }: any) => (
    <input data-testid="media-field" value={value} onChange={(e) => onChange(e.target.value)} />
  ),
}));
import { RepeaterField } from './RepeaterField';
import type { RepeaterRows } from './RepeaterField';

const tr = (_k: string, f: string) => f;

function Harness({ initial = {} as RepeaterRows, onDelete = vi.fn() }) {
  const [value, setValue] = useState<RepeaterRows>(initial);
  return (
    <>
      <RepeaterField fieldKey="slides" admin_label="Slides" value={value} onChange={setValue} onDelete={onDelete} tr={tr} />
      <pre data-testid="state">{JSON.stringify(value)}</pre>
    </>
  );
}
const state = () => JSON.parse(screen.getByTestId('state').textContent || '{}');

const oneRow: RepeaterRows = {
  'row-0': { title: { type: 'text', admin_label: 'Title', value: 'Hello' } },
};

describe('RepeaterField', () => {
  it('edits an item value inside a row', () => {
    render(<Harness initial={oneRow} />);
    fireEvent.change(screen.getByLabelText('cf-repeater-slides-row-0-title'), { target: { value: 'Changed' } });
    expect(state()['row-0'].title.value).toBe('Changed');
  });

  it('adds a row cloning the schema with empty values', () => {
    render(<Harness initial={oneRow} />);
    fireEvent.click(screen.getByTestId('cf-repeater-slides-addrow'));
    const s = state();
    expect(Object.keys(s)).toEqual(['row-0', 'row-1']);
    expect(s['row-1'].title).toEqual({ type: 'text', admin_label: 'Title', value: '' });
  });

  it('removes a row', () => {
    const two: RepeaterRows = {
      'row-0': { title: { type: 'text', admin_label: 'Title', value: 'a' } },
      'row-1': { title: { type: 'text', admin_label: 'Title', value: 'b' } },
    };
    render(<Harness initial={two} />);
    fireEvent.click(screen.getByTestId('cf-repeater-slides-removerow-row-0'));
    expect(Object.keys(state())).toEqual(['row-1']);
  });

  it('creates row-0 when the first sub-field is added to an empty repeater', () => {
    render(<Harness initial={{}} />);
    fireEvent.change(screen.getByLabelText('cf-repeater-slides-addlabel'), { target: { value: 'Title' } });
    fireEvent.click(screen.getByTestId('cf-repeater-slides-addfield'));
    expect(state()).toEqual({ 'row-0': { title: { type: 'text', admin_label: 'Title', value: '' } } });
  });

  it('adds a sub-field to every existing row', () => {
    render(<Harness initial={oneRow} />);
    fireEvent.change(screen.getByLabelText('cf-repeater-slides-addlabel'), { target: { value: 'Subtitle' } });
    fireEvent.click(screen.getByTestId('cf-repeater-slides-addfield'));
    const s = state();
    expect(s['row-0'].title.value).toBe('Hello'); // existing value kept
    expect(s['row-0'].subtitle).toEqual({ type: 'text', admin_label: 'Subtitle', value: '' });
  });

  it('removes a sub-field from every row', () => {
    render(<Harness initial={oneRow} />);
    fireEvent.click(screen.getByTestId('cf-repeater-slides-delfield-title'));
    expect(state()['row-0']).toEqual({});
  });

  it('deletes the whole group via onDelete', () => {
    const onDelete = vi.fn();
    render(<Harness initial={oneRow} onDelete={onDelete} />);
    fireEvent.click(screen.getByTestId('cf-repeater-delete-slides'));
    expect(onDelete).toHaveBeenCalled();
  });
});

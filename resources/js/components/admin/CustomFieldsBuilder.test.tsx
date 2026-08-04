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
import { CustomFieldsBuilder, slugify } from './CustomFieldsBuilder';
import type { CustomFieldsMap } from './CustomFieldsBuilder';

type CF = CustomFieldsMap;

function Harness({ initial = {} as CF, cats = [{ category_id: 1, title: 'News' }] }) {
  const [value, setValue] = useState<CF>(initial);
  return (
    <>
      <CustomFieldsBuilder value={value} categories={cats} onChange={setValue} />
      <pre data-testid="state">{JSON.stringify(value)}</pre>
    </>
  );
}
const state = () => JSON.parse(screen.getByTestId('state').textContent || '{}');

describe('slugify', () => {
  it('kebab-cases a label into a field key', () => {
    expect(slugify('Hello World')).toBe('hello-world');
    expect(slugify('About Big Text!')).toBe('about-big-text');
    expect(slugify('  Trim--Me  ')).toBe('trim-me');
  });

  it('transliterates non-latin labels instead of dropping them', () => {
    // Cyrillic would otherwise slugify to '' → a silent no-op on add.
    expect(slugify('Заголовок')).toBe('zagolovok');
    expect(slugify('Überschrift')).toBe('uberschrift');
  });
});

describe('CustomFieldsBuilder', () => {
  it('adds a text field keyed by the slug of its label', () => {
    render(<Harness />);
    fireEvent.change(screen.getByLabelText('cf-add-label'), { target: { value: 'Headline' } });
    fireEvent.click(screen.getByTestId('cf-add'));
    expect(state()).toEqual({ headline: { type: 'text', admin_label: 'Headline', value: '' } });
  });

  it('edits a field value in place', () => {
    render(<Harness initial={{ headline: { type: 'text', admin_label: 'Headline', value: '' } }} />);
    fireEvent.change(screen.getByLabelText('cf-value-headline'), { target: { value: 'Welcome' } });
    expect(state().headline.value).toBe('Welcome');
  });

  it('edits a link field into the {label,url,target} shape', () => {
    render(<Harness initial={{ cta: { type: 'link', admin_label: 'CTA', value: { label: '', url: '', target: '0' } } }} />);
    fireEvent.change(screen.getByLabelText('cf-link-url-cta'), { target: { value: 'https://x.com' } });
    fireEvent.click(screen.getByLabelText('cf-link-target-cta'));
    expect(state().cta.value).toEqual({ label: '', url: 'https://x.com', target: '1' });
  });

  it('removes a field', () => {
    render(<Harness initial={{ headline: { type: 'text', admin_label: 'Headline', value: 'x' } }} />);
    fireEvent.click(screen.getByTestId('cf-remove-headline'));
    expect(state()).toEqual({});
  });

  it('preserves an existing repeater untouched (no remove, passthrough on edit)', () => {
    const repeater = { type: 'repeater', admin_label: 'Slides', value: { 'row-0': { a: { type: 'text', admin_label: 'A', value: '1' } } } };
    render(<Harness initial={{ slides: repeater as any }} />);
    expect(screen.getByTestId('cf-repeater-slides')).toBeInTheDocument();
    expect(screen.queryByTestId('cf-remove-slides')).not.toBeInTheDocument();

    // Adding another field must keep the repeater's value intact.
    fireEvent.change(screen.getByLabelText('cf-add-label'), { target: { value: 'Title' } });
    fireEvent.click(screen.getByTestId('cf-add'));
    expect(state().slides).toEqual(repeater);
    expect(state().title).toEqual({ type: 'text', admin_label: 'Title', value: '' });
  });
});

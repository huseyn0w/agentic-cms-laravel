import { render, screen, fireEvent } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

// A self-returning chain stub so any editor.chain().focus().toggleX()…run() works.
const chainStub: any = new Proxy(function () {}, {
  get: (_t, p) => (p === 'run' ? () => undefined : () => chainStub),
  apply: () => chainStub,
});

let capturedOnUpdate: ((p: { editor: any }) => void) | undefined;
const fakeEditor: any = {
  getHTML: () => '<p>hi</p>',
  isActive: () => false,
  chain: () => chainStub,
  commands: { setContent: vi.fn() },
  can: () => ({ undo: () => true, redo: () => true }),
};

// TipTap/ProseMirror can't run in jsdom — mock at the module boundary and
// assert the wrapper's wiring (value render, onChange forward, image hook,
// source toggle), not ProseMirror internals.
vi.mock('@tiptap/react', () => ({
  useEditor: (opts: any) => {
    capturedOnUpdate = opts.onUpdate;
    return fakeEditor;
  },
  EditorContent: () => <div data-testid="editor-content" />,
}));
vi.mock('@tiptap/starter-kit', () => ({ default: { configure: () => ({}) } }));
vi.mock('@tiptap/extension-image', () => ({ default: {} }));
vi.mock('@tiptap/extension-link', () => ({ default: { configure: () => ({}) } }));

import { RichText } from './RichText';

describe('RichText (TipTap)', () => {
  it('renders the toolbar, editor content, and a hidden input carrying name+value', () => {
    render(<RichText id="body" name="content" value="<p>hi</p>" onChange={vi.fn()} />);
    expect(screen.getByTestId('richtext-body')).toBeInTheDocument();
    expect(screen.getByTestId('richtext-content-body')).toBeInTheDocument();
    expect(screen.getByTestId('editor-content')).toBeInTheDocument();
    expect(screen.getByLabelText('bold')).toBeInTheDocument();
    const hidden = document.querySelector('input[type="hidden"][name="content"]') as HTMLInputElement;
    expect(hidden).toBeTruthy();
    expect(hidden.value).toBe('<p>hi</p>');
  });

  it('forwards editor changes to onChange with the editor HTML', () => {
    const onChange = vi.fn();
    render(<RichText id="body" name="content" value="<p>hi</p>" onChange={onChange} />);
    capturedOnUpdate?.({ editor: fakeEditor });
    expect(onChange).toHaveBeenCalledWith('<p>hi</p>');
  });

  it('delegates image insertion to onPickImage when provided', () => {
    const onPickImage = vi.fn();
    render(<RichText id="body" name="content" value="" onChange={vi.fn()} onPickImage={onPickImage} />);
    fireEvent.click(screen.getByLabelText('image'));
    expect(onPickImage).toHaveBeenCalledTimes(1);
    const insert = onPickImage.mock.calls[0][0] as (url: string) => void;
    expect(() => insert('/storage/x.jpg')).not.toThrow();
  });

  it('toggles a raw HTML source textarea that forwards edits', () => {
    const onChange = vi.fn();
    render(<RichText id="body" name="content" value="<p>hi</p>" onChange={onChange} />);
    expect(screen.queryByTestId('richtext-source-body')).not.toBeInTheDocument();
    fireEvent.click(screen.getByLabelText('source'));
    const ta = screen.getByTestId('richtext-source-body');
    expect(ta).toHaveValue('<p>hi</p>');
    fireEvent.change(ta, { target: { value: '<p>edited</p>' } });
    expect(onChange).toHaveBeenCalledWith('<p>edited</p>');
  });
});

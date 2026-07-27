import { render, screen, fireEvent } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@tinymce/tinymce-react', () => ({
  Editor: ({ value, onEditorChange, id }: any) => (
    <textarea data-testid={`tinymce-${id}`} value={value}
      onChange={(e) => onEditorChange(e.target.value)} />
  ),
}));
// Prevent the self-hosted tinymce side-effect imports from loading in jsdom.
// The core + every plugin RichText.tsx bundles are stubbed: real v4
// plugin.js files reference a bare `tinymce` global that only exists once
// the real core has run, so each import needs its own no-op stub here.
vi.mock('tinymce/tinymce', () => ({}));
vi.mock('tinymce/themes/modern', () => ({}));
vi.mock('tinymce/plugins/advlist', () => ({}));
vi.mock('tinymce/plugins/autolink', () => ({}));
vi.mock('tinymce/plugins/lists', () => ({}));
vi.mock('tinymce/plugins/link', () => ({}));
vi.mock('tinymce/plugins/image', () => ({}));
vi.mock('tinymce/plugins/charmap', () => ({}));
vi.mock('tinymce/plugins/print', () => ({}));
vi.mock('tinymce/plugins/preview', () => ({}));
vi.mock('tinymce/plugins/hr', () => ({}));
vi.mock('tinymce/plugins/anchor', () => ({}));
vi.mock('tinymce/plugins/pagebreak', () => ({}));
vi.mock('tinymce/plugins/searchreplace', () => ({}));
vi.mock('tinymce/plugins/wordcount', () => ({}));
vi.mock('tinymce/plugins/visualblocks', () => ({}));
vi.mock('tinymce/plugins/visualchars', () => ({}));
vi.mock('tinymce/plugins/code', () => ({}));
vi.mock('tinymce/plugins/fullscreen', () => ({}));
vi.mock('tinymce/plugins/insertdatetime', () => ({}));
vi.mock('tinymce/plugins/media', () => ({}));
vi.mock('tinymce/plugins/nonbreaking', () => ({}));
vi.mock('tinymce/plugins/save', () => ({}));
vi.mock('tinymce/plugins/table', () => ({}));
vi.mock('tinymce/plugins/contextmenu', () => ({}));
vi.mock('tinymce/plugins/directionality', () => ({}));
vi.mock('tinymce/plugins/emoticons', () => ({}));
vi.mock('tinymce/plugins/template', () => ({}));
vi.mock('tinymce/plugins/paste', () => ({}));
vi.mock('tinymce/plugins/textcolor', () => ({}));
vi.mock('tinymce/plugins/colorpicker', () => ({}));
vi.mock('tinymce/plugins/textpattern', () => ({}));
vi.mock('tinymce/skins/lightgray/skin.min.css', () => ({}));
vi.mock('tinymce/skins/lightgray/content.min.css', () => ({}));

import { RichText } from './RichText';

describe('RichText', () => {
  it('renders with the initial value and emits changes', () => {
    const onChange = vi.fn();
    render(<RichText id="content" name="content" value="<p>hi</p>" onChange={onChange} />);
    const ed = screen.getByTestId('tinymce-content');
    expect(ed).toHaveValue('<p>hi</p>');
    fireEvent.change(ed, { target: { value: '<p>bye</p>' } });
    expect(onChange).toHaveBeenCalledWith('<p>bye</p>');
  });
});

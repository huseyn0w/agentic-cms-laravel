import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, expect, it, vi, beforeEach } from 'vitest';

vi.mock('@inertiajs/react', () => ({ Head: () => null }));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
import Index from './Index';

const props = (over = {}) => ({
  upload_endpoint: '/filemanager/upload?type=Files&working_dir=%2F',
  library_src: '/filemanager',
  ...over,
});

beforeEach(() => {
  document.head.innerHTML = '<meta name="csrf-token" content="tok123">';
  vi.stubGlobal('fetch', vi.fn(() => Promise.resolve({ ok: true, text: () => Promise.resolve('OK') })));
});

describe('Media index', () => {
  it('renders the dropzone, file input and the LFM library iframe', () => {
    render(<Index {...props()} />);
    expect(screen.getByTestId('media-dropzone')).toBeInTheDocument();
    expect(screen.getByTestId('media-file-input')).toBeInTheDocument();
    const iframe = screen.getByTitle('Media');
    expect(iframe).toHaveAttribute('src', '/filemanager');
  });

  it('uploads selected files to the LFM endpoint with the CSRF header', async () => {
    render(<Index {...props()} />);
    const input = screen.getByTestId('media-file-input') as HTMLInputElement;
    const file = new File(['x'], 'a.png', { type: 'image/png' });
    fireEvent.change(input, { target: { files: [file] } });

    await waitFor(() => expect(fetch).toHaveBeenCalled());
    const [url, opts] = (fetch as any).mock.calls[0];
    expect(url).toBe('/filemanager/upload?type=Files&working_dir=%2F');
    expect(opts.method).toBe('POST');
    expect(opts.headers['X-CSRF-TOKEN']).toBe('tok123');
    expect(opts.body).toBeInstanceOf(FormData);
  });
});

import { Head } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import type { ChangeEvent, DragEvent, ReactElement } from 'react';

interface Props {
  upload_endpoint: string;
  library_src: string;
}

type Status = '' | 'uploading' | 'success' | 'error';

function csrfToken(): string {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

/**
 * Media library. The browse/rename/crop/delete UI is the third-party Laravel
 * FileManager, hosted in the iframe (unchanged by the Inertia migration). The
 * dropzone is the first-party upload surface: it posts real files to LFM's own
 * upload endpoint and reloads the iframe on success, keeping a keyboard/AT path
 * independent of the iframe (mirrors the legacy Alpine mediaDropzone).
 */
export default function Index({ upload_endpoint, library_src }: Props) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const iframeRef = useRef<HTMLIFrameElement>(null);
  const [dragging, setDragging] = useState(false);
  const [status, setStatus] = useState<Status>('');
  const [message, setMessage] = useState('');

  const upload = async (files: FileList) => {
    if (files.length === 0) return;
    const token = csrfToken();
    const body = new FormData();
    Array.prototype.forEach.call(files, (f: File) => body.append('upload[]', f));
    body.append('_token', token);

    setStatus('uploading');
    setMessage(tr('cpanel/media.dropzone_uploading', 'Uploading…'));

    try {
      const res = await fetch(upload_endpoint, {
        method: 'POST',
        body,
        headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });
      const text = await res.text();
      // LFM returns "OK" (or a JSON payload) on success, an error string
      // otherwise; a non-2xx or an error body is a failure.
      if (!res.ok || /error/i.test(text)) {
        setStatus('error');
        setMessage(tr('cpanel/media.dropzone_error', 'Upload failed. Please try again.'));
        return;
      }
      setStatus('success');
      setMessage(tr('cpanel/media.dropzone_success', 'Upload complete. Refreshing the library…'));
      iframeRef.current?.contentWindow?.location.reload();
    } catch {
      setStatus('error');
      setMessage(tr('cpanel/media.dropzone_error', 'Upload failed. Please try again.'));
    }
  };

  const onDrop = (e: DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    setDragging(false);
    if (e.dataTransfer?.files.length) upload(e.dataTransfer.files);
  };
  const onSelect = (e: ChangeEvent<HTMLInputElement>) => {
    if (e.target.files?.length) upload(e.target.files);
  };

  return (
    <>
      <Head title={tr('cpanel/media.headline', 'Media')} />
      <div className="mx-auto max-w-7xl">
        <div className="mb-5">
          <h1 className="text-[22px] font-semibold tracking-tight">{tr('cpanel/media.headline', 'Media')}</h1>
        </div>

        <div
          data-testid="media-dropzone"
          onDragOver={(e) => { e.preventDefault(); setDragging(true); }}
          onDragEnter={(e) => { e.preventDefault(); setDragging(true); }}
          onDragLeave={(e) => { e.preventDefault(); setDragging(false); }}
          onDrop={onDrop}
          className={`group relative mb-6 rounded-lg border-2 border-dashed bg-surface-2 px-6 py-10 text-center transition-colors ${
            dragging ? 'border-primary bg-primary/5' : 'admin-sep'
          }`}
        >
          <label className="flex cursor-pointer flex-col items-center gap-3">
            <span className="text-sm font-medium text-fg">{tr('cpanel/media.dropzone_prompt', 'Drag files here or click to upload')}</span>
            <span className="text-xs text-faint">{tr('cpanel/media.dropzone_hint', 'Images and documents.')}</span>
            <input type="file" name="upload[]" multiple className="sr-only"
              aria-label={tr('cpanel/media.dropzone_input_label', 'Upload files to the media library')}
              onChange={onSelect} data-testid="media-file-input" />
            <span className="mt-1 inline-flex h-9 items-center rounded-md bg-surface px-4 text-sm font-medium text-fg ring-1 ring-inset ring-[color:var(--border)]">
              {tr('cpanel/media.dropzone_button', 'Choose files')}
            </span>
          </label>
          {message && (
            <p role="status" aria-live="polite"
              className={`mt-4 text-sm ${status === 'error' ? 'text-error' : 'text-muted'}`}>
              {message}
            </p>
          )}
        </div>

        <div className="admin-card overflow-hidden">
          <div className="flex items-center gap-3 border-b admin-sep px-5 py-3">
            <span className="text-sm text-muted">{tr('cpanel/media.headline', 'Media')}</span>
          </div>
          <iframe ref={iframeRef} src={library_src} className="block w-full border-0"
            style={{ height: '70vh', minHeight: '500px' }} title={tr('cpanel/media.headline', 'Media')} />
        </div>
      </div>
    </>
  );
}

Index.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Media">{page}</AdminLayout>
);

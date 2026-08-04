import { useEditor, EditorContent } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import Link from '@tiptap/extension-link';
import { useEffect, useState, type MouseEvent } from 'react';

// Self-hosted TipTap 3 (bundled via Vite, no CDN). Controlled editor: `value`
// is the HTML source of truth, `onChange` fires editor.getHTML() on every edit.
// Image insertion is delegated to `onPickImage` (wired to laravel-filemanager
// by the Posts form); with no picker it falls back to a URL prompt. The content
// HTML is sanitized server-side by mews/purifier on write — not here.

const BTN =
  'inline-flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-[12.5px] font-medium text-muted transition hover:bg-black/[.035] hover:text-fg disabled:opacity-40';
const BTN_ON = 'admin-nav-active text-fg';

export function RichText({
  id,
  name,
  value,
  onChange,
  height = 300,
  onPickImage,
}: {
  id: string;
  name: string;
  value: string;
  onChange: (html: string) => void;
  height?: number;
  onPickImage?: (insert: (url: string) => void) => void;
}) {
  const [source, setSource] = useState(false);

  const editor = useEditor({
    extensions: [
      StarterKit.configure({ link: false }),
      Image,
      Link.configure({ openOnClick: false }),
    ],
    content: value,
    onUpdate: ({ editor }) => onChange(editor.getHTML()),
  });

  // Reflect external value changes (form reset, language switch) into the editor
  // without re-emitting an update (guarded to avoid a render loop).
  useEffect(() => {
    if (editor && !source && value !== editor.getHTML()) {
      editor.commands.setContent(value, { emitUpdate: false });
    }
  }, [value, editor, source]);

  const insertImage = (url: string) => editor?.chain().focus().setImage({ src: url }).run();
  const pickImage = () => {
    if (onPickImage) onPickImage(insertImage);
    else {
      const url = window.prompt('Image URL');
      if (url) insertImage(url);
    }
  };
  const applyLink = () => {
    const href = window.prompt('Link URL') ?? '';
    if (href) editor?.chain().focus().setLink({ href }).run();
    else editor?.chain().focus().unsetLink().run();
  };

  const run = (fn: () => void) => (e: MouseEvent) => {
    e.preventDefault();
    fn();
  };
  const cls = (isOn: boolean) => `${BTN} ${isOn ? BTN_ON : ''}`;
  const on = (nodeOrMark: string, attrs?: Record<string, unknown>) =>
    !!editor?.isActive(nodeOrMark, attrs);

  return (
    <div data-testid={`richtext-${id}`} className="admin-card overflow-hidden">
      <div className="flex flex-wrap items-center gap-1 border-b admin-sep bg-surface-2 px-2 py-1.5">
        <button type="button" aria-label="bold" className={cls(on('bold'))}
          onClick={run(() => editor?.chain().focus().toggleBold().run())}><b>B</b></button>
        <button type="button" aria-label="italic" className={cls(on('italic'))}
          onClick={run(() => editor?.chain().focus().toggleItalic().run())}><i>I</i></button>
        <span className="mx-1 h-5 w-px bg-black/10" aria-hidden />
        <button type="button" aria-label="heading-2" className={cls(on('heading', { level: 2 }))}
          onClick={run(() => editor?.chain().focus().toggleHeading({ level: 2 }).run())}>H2</button>
        <button type="button" aria-label="heading-3" className={cls(on('heading', { level: 3 }))}
          onClick={run(() => editor?.chain().focus().toggleHeading({ level: 3 }).run())}>H3</button>
        <span className="mx-1 h-5 w-px bg-black/10" aria-hidden />
        <button type="button" aria-label="bullet-list" className={cls(on('bulletList'))}
          onClick={run(() => editor?.chain().focus().toggleBulletList().run())}>•</button>
        <button type="button" aria-label="ordered-list" className={cls(on('orderedList'))}
          onClick={run(() => editor?.chain().focus().toggleOrderedList().run())}>1.</button>
        <button type="button" aria-label="blockquote" className={cls(on('blockquote'))}
          onClick={run(() => editor?.chain().focus().toggleBlockquote().run())}>&ldquo;</button>
        <span className="mx-1 h-5 w-px bg-black/10" aria-hidden />
        <button type="button" aria-label="link" className={cls(on('link'))} onClick={run(applyLink)}>Link</button>
        <button type="button" aria-label="image" className={BTN} onClick={run(pickImage)}>Image</button>
        <span className="mx-1 h-5 w-px bg-black/10" aria-hidden />
        <button type="button" aria-label="undo" className={BTN}
          onClick={run(() => editor?.chain().focus().undo().run())}>&#8630;</button>
        <button type="button" aria-label="redo" className={BTN}
          onClick={run(() => editor?.chain().focus().redo().run())}>&#8631;</button>
        <button type="button" aria-label="source" aria-pressed={source}
          className={cls(source)} onClick={run(() => setSource((s) => !s))}>&lt;/&gt;</button>
      </div>

      {source ? (
        <textarea
          data-testid={`richtext-source-${id}`}
          value={value}
          onChange={(e) => onChange(e.target.value)}
          style={{ minHeight: height }}
          className="block w-full resize-y bg-transparent p-3 font-mono text-[12.5px] leading-relaxed text-fg outline-none"
        />
      ) : (
        <div
          data-testid={`richtext-content-${id}`}
          style={{ minHeight: height }}
          className="prose prose-sm max-w-none p-3 text-fg [&_.ProseMirror]:min-h-[inherit] [&_.ProseMirror]:outline-none"
        >
          <EditorContent editor={editor} />
        </div>
      )}

      <input type="hidden" id={id} name={name} value={value} readOnly />
    </div>
  );
}

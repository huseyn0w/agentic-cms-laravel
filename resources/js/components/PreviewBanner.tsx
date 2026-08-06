import { useTranslation } from 'react-i18next';

/**
 * Sticky noindex strip shown at the top of a public page when it is being
 * previewed from the admin (an unpublished draft or a future-scheduled post).
 * Rendered by Post/Page/Home/Contact behind their `preview` prop.
 */
export function PreviewBanner() {
    const { t } = useTranslation();
    const key = 'cpanel/posts.preview_banner';
    const label = t(key);
    const text = label === key ? 'Preview — draft, not published. This page is not indexed.' : label;

    return (
        <div
            data-testid="preview-banner"
            className="sticky top-0 z-50 flex items-center justify-center gap-2 bg-[var(--text)] px-4 py-2 text-center text-xs font-medium text-[var(--bg)]"
        >
            <span className="inline-block h-1.5 w-1.5 rounded-full bg-current opacity-70" />
            {text}
        </div>
    );
}

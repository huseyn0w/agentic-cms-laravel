import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import type { Paginator } from '@/lib/types';

/**
 * Compact list pager for the admin resource lists. Reads the paginator meta
 * Inertia serializes (prev_page_url/next_page_url/from/to) and navigates with
 * Inertia <Link> so paging is instant. Renders only the range summary when
 * there is a single page.
 */
export function Pagination({ meta }: { meta: Omit<Paginator<unknown>, 'data'> }) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const { current_page, last_page, from, to, total, prev_page_url, next_page_url } = meta;

  const linkCls = 'rounded-md px-2.5 py-1 font-medium text-fg hover:bg-surface-2';
  const disabledCls = 'rounded-md px-2.5 py-1 text-faint';

  return (
    <div className="flex items-center justify-between px-4 py-3 text-[12.5px] text-muted">
      <span>{from ?? 0}–{to ?? 0} {tr('cpanel/common.of', 'of')} {total}</span>
      {last_page > 1 && (
        <div className="flex items-center gap-1.5">
          {prev_page_url ? (
            <Link href={prev_page_url} prefetch cacheFor="15s" className={linkCls} data-testid="page-prev">
              {tr('cpanel/common.prev', 'Previous')}
            </Link>
          ) : (
            <span className={disabledCls} data-testid="page-prev-disabled">{tr('cpanel/common.prev', 'Previous')}</span>
          )}
          <span className="px-1 tabular-nums">{current_page} / {last_page}</span>
          {next_page_url ? (
            <Link href={next_page_url} prefetch cacheFor="15s" className={linkCls} data-testid="page-next">
              {tr('cpanel/common.next', 'Next')}
            </Link>
          ) : (
            <span className={disabledCls} data-testid="page-next-disabled">{tr('cpanel/common.next', 'Next')}</span>
          )}
        </div>
      )}
    </div>
  );
}

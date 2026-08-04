import { Head, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/Button';

interface RevisionRow {
  id: number;
  version: number;
  author: string | null;
  created_at: string;
}
interface RevisionsViewProps {
  /** Resource base URL, e.g. '/agentic-cms-laravel-admin/posts'. */
  base: string;
  entity_id: number;
  lang: string;
  revisions: {
    data: RevisionRow[];
    current_page: number;
    last_page: number;
    total: number;
  };
}

/**
 * Presentational revision-history table shared by posts and pages. The only
 * per-resource difference is the `base` URL used to build compare/restore/back
 * links, so the owning page component passes it plus the persistent layout.
 */
export function RevisionsView({ base, entity_id, lang, revisions }: RevisionsViewProps) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const rows = revisions.data;

  const restore = (revisionId: number) => {
    router.post(
      `${base}/${entity_id}/revisions/${revisionId}/restore/${lang}`,
      {},
      {
        onBefore: () =>
          window.confirm(
            tr(
              'cpanel/revisions.restore_confirm',
              'Restore this version? The current content is snapshotted first, so you can undo it.',
            ),
          ),
      },
    );
  };

  const heading = tr('cpanel/revisions.headline', 'Revision history');

  return (
    <>
      <Head title={heading} />
      <div className="mb-5 flex items-center gap-4">
        <div>
          <h1 className="text-[22px] font-semibold tracking-tight">{heading}</h1>
          <p className="mt-1 text-sm text-muted">
            {tr(
              'cpanel/revisions.subtitle',
              'Every edit stores an immutable snapshot of the previous version. Compare or restore any of them.',
            )}
          </p>
        </div>
        <div className="ml-auto">
          <Button href={`${base}/${entity_id}/${lang}`} variant="outline" size="md">
            {tr('cpanel/revisions.back_to_editor', 'Back to editor')}
          </Button>
        </div>
      </div>

      <div className="admin-card overflow-hidden">
        <table className="w-full text-left text-sm">
          <thead className="bg-surface-2 text-xs uppercase text-faint">
            <tr>
              <th className="w-16 px-4 py-3 font-semibold">{tr('cpanel/revisions.table_revision', 'Revision')}</th>
              <th className="px-4 py-3 font-semibold">{tr('cpanel/revisions.table_author', 'Edited by')}</th>
              <th className="px-4 py-3 font-semibold">{tr('cpanel/revisions.table_date', 'Saved at')}</th>
              <th className="px-4 py-3 text-right font-semibold">{tr('cpanel/revisions.table_actions', 'Actions')}</th>
            </tr>
          </thead>
          <tbody>
            {rows.length === 0 ? (
              <tr>
                <td colSpan={4} className="px-4 py-10 text-center text-muted">
                  {tr('cpanel/revisions.empty', 'No revisions yet. The first edit will create one.')}
                </td>
              </tr>
            ) : (
              rows.map((r) => (
                <tr key={r.id} className="border-t admin-sep hover:bg-surface-2">
                  <td className="px-4 py-3 font-medium">v{r.version}</td>
                  <td className="px-4 py-3 text-muted">
                    {r.author ?? tr('cpanel/revisions.unknown_author', 'Unknown')}
                  </td>
                  <td className="whitespace-nowrap px-4 py-3 text-muted">{r.created_at}</td>
                  <td className="px-4 py-3">
                    <div className="flex items-center justify-end gap-3">
                      <a
                        href={`${base}/${entity_id}/revisions/${r.id}/compare/${lang}`}
                        className="font-medium text-fg underline-offset-2 hover:underline"
                      >
                        {tr('cpanel/revisions.compare', 'Compare')}
                      </a>
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => restore(r.id)}
                        data-testid={`restore-${r.id}`}
                      >
                        {tr('cpanel/revisions.restore', 'Restore this version')}
                      </Button>
                    </div>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </>
  );
}

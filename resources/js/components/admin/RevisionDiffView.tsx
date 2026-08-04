import { Head, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/Button';

interface DiffField {
  field: string;
  old: string | null;
  current: string | null;
  changed: boolean;
}
interface RevisionDiffViewProps {
  /** Resource base URL, e.g. '/agentic-cms-laravel-admin/posts'. */
  base: string;
  entity_id: number;
  lang: string;
  revision: { id: number; created_at: string };
  fields: DiffField[];
}

/**
 * Presentational per-field revision comparison shared by posts and pages.
 * Values are rendered as escaped text (snapshots may contain HTML).
 */
export function RevisionDiffView({ base, entity_id, lang, revision, fields }: RevisionDiffViewProps) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));

  const restore = () => {
    router.post(
      `${base}/${entity_id}/revisions/${revision.id}/restore/${lang}`,
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

  const heading = tr('cpanel/revisions.diff_headline', 'Compare revision');

  return (
    <>
      <Head title={heading} />
      <div className="mb-5 flex items-center gap-4">
        <div>
          <h1 className="text-[22px] font-semibold tracking-tight">{heading}</h1>
          <p className="mt-1 text-sm text-muted">
            {tr(
              'cpanel/revisions.diff_subtitle',
              'The snapshot is on the left, the current content on the right. Changed fields are highlighted.',
            )}
          </p>
          <p className="mt-1 font-mono text-xs text-faint">{revision.created_at}</p>
        </div>
        <div className="ml-auto">
          <Button href={`${base}/${entity_id}/revisions/${lang}`} variant="outline" size="md">
            {tr('cpanel/revisions.back_to_list', 'Back to revisions')}
          </Button>
        </div>
      </div>

      <div className="admin-card overflow-hidden">
        <table className="w-full text-left text-sm">
          <thead className="bg-surface-2 text-xs uppercase text-faint">
            <tr>
              <th className="w-40 px-4 py-3 font-semibold">{tr('cpanel/revisions.diff_field', 'Field')}</th>
              <th className="px-4 py-3 font-semibold">{tr('cpanel/revisions.diff_old', 'Revision (this snapshot)')}</th>
              <th className="px-4 py-3 font-semibold">{tr('cpanel/revisions.diff_current', 'Current')}</th>
            </tr>
          </thead>
          <tbody>
            {fields.map((f) => (
              <tr key={f.field} className={`border-t admin-sep ${f.changed ? 'bg-surface-2' : ''}`}>
                <td className="px-4 py-3 align-top">
                  <span className="font-medium">{f.field}</span>
                  <div className="mt-1 text-xs text-faint">
                    {f.changed
                      ? tr('cpanel/revisions.diff_changed', 'Changed')
                      : tr('cpanel/revisions.diff_unchanged', 'Unchanged')}
                  </div>
                </td>
                <td className="px-4 py-3 align-top">
                  <div className="max-h-64 overflow-auto whitespace-pre-wrap break-words font-mono text-muted">
                    {f.old}
                  </div>
                </td>
                <td className="px-4 py-3 align-top">
                  <div className="max-h-64 overflow-auto whitespace-pre-wrap break-words font-mono text-fg">
                    {f.current}
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        <div className="flex justify-end border-t admin-sep px-5 py-4">
          <Button variant="primary" size="md" onClick={restore} data-testid="diff-restore">
            {tr('cpanel/revisions.restore', 'Restore this version')}
          </Button>
        </div>
      </div>
    </>
  );
}

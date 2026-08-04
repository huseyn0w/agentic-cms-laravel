import { AdminLayout } from '@/layouts/AdminLayout';
import { RevisionDiffView } from '@/components/admin/RevisionDiffView';
import type { ReactElement } from 'react';

interface DiffField {
  field: string;
  old: string | null;
  current: string | null;
  changed: boolean;
}
interface RevisionDiffProps {
  entity_id: number;
  lang: string;
  revision: { id: number; created_at: string };
  fields: DiffField[];
}

const BASE = '/agentic-cms-laravel-admin/pages';

export default function RevisionDiff({ entity_id, lang, revision, fields }: RevisionDiffProps) {
  return <RevisionDiffView base={BASE} entity_id={entity_id} lang={lang} revision={revision} fields={fields} />;
}

RevisionDiff.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Pages / Revisions / Compare">{page}</AdminLayout>
);

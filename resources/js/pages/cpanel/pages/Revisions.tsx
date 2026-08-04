import { AdminLayout } from '@/layouts/AdminLayout';
import { RevisionsView } from '@/components/admin/RevisionsView';
import type { ReactElement } from 'react';

interface RevisionRow {
  id: number;
  version: number;
  author: string | null;
  created_at: string;
}
interface RevisionsProps {
  entity_id: number;
  lang: string;
  revisions: { data: RevisionRow[]; current_page: number; last_page: number; total: number };
}

const BASE = '/agentic-cms-laravel-admin/pages';

export default function Revisions({ entity_id, lang, revisions }: RevisionsProps) {
  return <RevisionsView base={BASE} entity_id={entity_id} lang={lang} revisions={revisions} />;
}

Revisions.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Pages / Revisions">{page}</AdminLayout>
);

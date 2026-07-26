import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import type { ReactElement } from 'react';

// NOTE: the underlying repository selects are narrower than a naive read of
// the row shape suggests — see CPanelPostRepository::latestWithTitles(),
// CPanelUserRepository::latestUsernames(), CPanelCommentRepository::latestComments().
// Posts select `id` + translated `title` (matches the brief). Users select
// ONLY `username` (no `id`, no `name`). Comments select ONLY `comment` (no
// `id`, no `body`). Field names below reflect the ACTUAL server payload;
// nothing was renamed server-side.
interface DashboardProps {
  posts: Array<{ id: number; title: string }>;
  users: Array<{ username: string }>;
  comments: Array<{ comment: string }>;
}

function Card({ title, items, render }: { title: string; items: any[]; render: (i: any) => string }) {
  return (
    <section className="admin-card p-4">
      <h3 className="mb-3 text-[13px] font-semibold">{title}</h3>
      <ul className="flex flex-col gap-2 text-sm text-muted">
        {items.length === 0 ? <li className="text-faint">—</li>
          : items.map((i, idx) => <li key={idx} className="truncate">{render(i)}</li>)}
      </ul>
    </section>
  );
}

export default function Dashboard({ posts, users, comments }: DashboardProps) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  return (
    <>
      <Head title={tr('cpanel/menu.dashboard', 'Dashboard')} />
      <div className="mb-5">
        <h1 className="text-[22px] font-semibold tracking-tight">{tr('cpanel/menu.dashboard', 'Dashboard')}</h1>
      </div>
      <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
        <Card title={tr('cpanel/menu.posts', 'Latest posts')} items={posts} render={(p) => p.title} />
        <Card title={tr('cpanel/menu.users', 'Latest users')} items={users} render={(u) => u.username} />
        <Card title={tr('cpanel/menu.comments', 'Latest comments')} items={comments} render={(c) => c.comment} />
      </div>
    </>
  );
}

Dashboard.layout = (page: ReactElement) => <AdminLayout breadcrumb="Admin">{page}</AdminLayout>;

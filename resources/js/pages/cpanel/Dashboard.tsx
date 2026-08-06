import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import type { ReactElement, ReactNode } from 'react';

// NOTE: the underlying repository selects are narrower than a naive read of
// the row shape suggests — see CPanelPostRepository::latestWithTitles(),
// CPanelUserRepository::latestUsernames(), CPanelCommentRepository::latestComments().
// Posts select `id` + translated `title` (matches the brief). Users select
// ONLY `username` (no `id`, no `name`). Comments select ONLY `comment` (no
// `id`, no `body`). Field names below reflect the ACTUAL server payload.
interface Counts {
  posts: number;
  users: number;
  comments: number;
  comments_pending: number;
  scheduled: number;
}
interface DashboardProps {
  posts: Array<{ id: number; title: string }>;
  users: Array<{ username: string }>;
  comments: Array<{ comment: string }>;
  counts?: Counts;
}

const ZERO: Counts = { posts: 0, users: 0, comments: 0, comments_pending: 0, scheduled: 0 };

function Stat({ label, value, caption, tone }: {
  label: string; value: number; caption: string; tone?: 'muted' | 'warning';
}) {
  return (
    <div className="admin-card p-4">
      <div className="text-[11.5px] font-semibold uppercase tracking-wide text-subtle">{label}</div>
      <div className="mt-2 text-[27px] font-semibold tabular-nums leading-none tracking-tight">{value}</div>
      <div className={`mt-1.5 text-[11.5px] tabular-nums ${tone === 'warning' ? 'text-warning' : 'text-faint'}`}>
        {caption}
      </div>
    </div>
  );
}

function Panel({ title, items, render }: { title: string; items: any[]; render: (i: any) => ReactNode }) {
  return (
    <section className="admin-card overflow-hidden">
      <div className="flex items-center justify-between border-b admin-sep px-4 py-3">
        <b className="text-[13px] font-semibold">{title}</b>
      </div>
      <ul className="flex flex-col">
        {items.length === 0 ? (
          <li className="px-4 py-3 text-sm text-faint">—</li>
        ) : (
          items.map((i, idx) => (
            <li key={idx} className="truncate border-b admin-sep px-4 py-2.5 text-[13px] text-muted last:border-b-0">
              {render(i)}
            </li>
          ))
        )}
      </ul>
    </section>
  );
}

export default function Dashboard({ posts, users, comments, counts = ZERO }: DashboardProps) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const pending = counts.comments_pending;

  return (
    <>
      <Head title={tr('cpanel/menu.dashboard', 'Dashboard')} />
      <div className="mb-5">
        <h1 className="text-[22px] font-semibold tracking-tight">{tr('cpanel/menu.dashboard', 'Dashboard')}</h1>
        <p className="mt-1 text-[13px] text-subtle">{tr('cpanel/dashboard.subtitle', 'Overview of your site')}</p>
      </div>

      <div className="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <Stat label={tr('cpanel/menu.posts', 'Posts')} value={counts.posts}
          caption={tr('cpanel/dashboard.total_posts', 'total posts')} />
        <Stat label={tr('cpanel/menu.comments', 'Comments')} value={counts.comments}
          caption={pending > 0
            ? `${pending} ${tr('cpanel/dashboard.pending', 'awaiting review')}`
            : tr('cpanel/dashboard.all_approved', 'all approved')}
          tone={pending > 0 ? 'warning' : 'muted'} />
        <Stat label={tr('cpanel/menu.users', 'Users')} value={counts.users}
          caption={tr('cpanel/dashboard.registered', 'registered')} />
        <Stat label={tr('cpanel/dashboard.scheduled', 'Scheduled')} value={counts.scheduled}
          caption={tr('cpanel/dashboard.in_queue', 'in the queue')} />
      </div>

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div className="lg:col-span-1">
          <Panel title={tr('cpanel/dashboard.latest_posts', 'Latest posts')} items={posts}
            render={(p) => p.title} />
        </div>
        <div className="lg:col-span-1">
          <Panel title={tr('cpanel/dashboard.latest_users', 'Latest users')} items={users}
            render={(u) => u.username} />
        </div>
        <div className="lg:col-span-1">
          <Panel title={tr('cpanel/dashboard.latest_comments', 'Latest comments')} items={comments}
            render={(c) => c.comment} />
        </div>
      </div>
    </>
  );
}

Dashboard.layout = (page: ReactElement) => <AdminLayout breadcrumb="Admin">{page}</AdminLayout>;

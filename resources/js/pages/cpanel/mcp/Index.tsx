import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import type { ReactElement } from 'react';

interface Props {
  endpoint: string;
  discoveryUrl: string;
}

function CopyRow({ value, label }: { value: string; label: string }) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const [copied, setCopied] = useState(false);

  const copy = async () => {
    try {
      await navigator.clipboard.writeText(value);
      setCopied(true);
      setTimeout(() => setCopied(false), 1500);
    } catch {
      setCopied(false);
    }
  };

  return (
    <div>
      <div className="mb-1 text-[12px] font-semibold uppercase tracking-wide text-muted">{label}</div>
      <div className="flex items-center gap-2">
        <code className="admin-bevel min-w-0 flex-1 truncate rounded-md bg-surface px-3 py-2 text-[13px] text-fg">{value}</code>
        <button
          type="button"
          onClick={copy}
          className="shrink-0 rounded-md bg-primary px-3 py-2 text-[13px] font-medium text-primary-contrast transition-opacity hover:opacity-90"
        >
          {copied ? tr('cpanel/mcp.copied', 'Copied') : tr('cpanel/mcp.copy', 'Copy')}
        </button>
      </div>
    </div>
  );
}

export default function Index({ endpoint, discoveryUrl }: Props) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));

  const steps = [
    tr('cpanel/mcp.step_1', 'Open your MCP client (e.g. Claude) and add a custom connector.'),
    tr('cpanel/mcp.step_2', 'Paste the server URL below as the connector endpoint.'),
    tr('cpanel/mcp.step_3', 'When prompted, authorize access and sign in with your admin account.'),
    tr('cpanel/mcp.step_4', 'The client registers itself over OAuth 2.1 — no manual token needed.'),
  ];

  return (
    <>
      <Head title={tr('cpanel/mcp.title', 'MCP connection')} />
      <div className="mx-auto max-w-3xl space-y-6">
        <div>
          <h1 className="text-xl font-semibold tracking-tight text-fg">{tr('cpanel/mcp.title', 'MCP connection')}</h1>
          <p className="mt-1 text-[14px] text-muted">
            {tr('cpanel/mcp.subtitle', 'Connect an AI assistant to manage this site through the built-in MCP server.')}
          </p>
        </div>

        <div className="admin-bevel space-y-4 rounded-lg bg-surface-2 p-5">
          <CopyRow value={endpoint} label={tr('cpanel/mcp.endpoint_label', 'Server endpoint')} />
          <CopyRow value={discoveryUrl} label={tr('cpanel/mcp.discovery_label', 'OAuth discovery URL')} />
        </div>

        <div className="admin-bevel rounded-lg bg-surface-2 p-5">
          <h2 className="mb-3 text-[13px] font-semibold uppercase tracking-wide text-muted">
            {tr('cpanel/mcp.steps_heading', 'How to connect')}
          </h2>
          <ol className="list-decimal space-y-2 pl-5 text-[14px] text-fg">
            {steps.map((step, i) => (
              <li key={i}>{step}</li>
            ))}
          </ol>
          <p className="mt-4 text-[13px] text-muted">
            {tr(
              'cpanel/mcp.oauth_note',
              'Access is protected by OAuth 2.1. Each tool still enforces your admin permissions, so a connected client can only do what your account is allowed to.',
            )}
          </p>
        </div>
      </div>
    </>
  );
}

Index.layout = (page: ReactElement) => <AdminLayout breadcrumb="Admin / MCP">{page}</AdminLayout>;

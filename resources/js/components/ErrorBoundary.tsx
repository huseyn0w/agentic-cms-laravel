import { Component } from 'react';
import i18n from 'i18next';
import type { ErrorInfo, ReactNode } from 'react';

interface Props {
  children: ReactNode;
}
interface State {
  error: Error | null;
}

/**
 * App-wide error boundary. Catches render/lifecycle errors anywhere in the
 * React tree so a single broken page shows a recoverable fallback instead of a
 * blank screen. Styling is inline on purpose: the boundary must render even
 * when the CSS build, the theme wrapper, or a shared layout is the thing that
 * failed. Copy is localized through the i18next singleton (not the react-i18next
 * hook) with English defaults, so it works even if the failure is inside a
 * translation-consuming subtree.
 */
export class ErrorBoundary extends Component<Props, State> {
  state: State = { error: null };

  static getDerivedStateFromError(error: Error): State {
    return { error };
  }

  componentDidCatch(error: Error, info: ErrorInfo): void {
    // Surface for logging/observability; the fallback UI stays user-facing.
    console.error('Unhandled UI error:', error, info.componentStack);
  }

  private reload = (): void => {
    window.location.reload();
  };

  render(): ReactNode {
    const { error } = this.state;
    if (!error) return this.props.children;

    const t = (key: string, fallback: string): string => {
      const value = i18n.isInitialized ? i18n.t(key, { defaultValue: fallback }) : fallback;
      return typeof value === 'string' ? value : fallback;
    };
    const isDev = Boolean(import.meta.env?.DEV);

    return (
      <div
        role="alert"
        style={{
          minHeight: '100vh',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          padding: '24px',
          background: '#09090b',
          color: '#fafafa',
          fontFamily: 'ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif',
        }}
      >
        <div style={{ maxWidth: '480px', textAlign: 'center' }}>
          <h1 style={{ margin: '0 0 8px', fontSize: '20px', fontWeight: 600 }}>
            {t('errors.boundary_title', 'Something went wrong')}
          </h1>
          <p style={{ margin: '0 0 20px', fontSize: '14px', lineHeight: 1.6, color: '#a1a1aa' }}>
            {t('errors.boundary_body', 'The page hit an unexpected error. Reloading usually fixes it.')}
          </p>
          <button
            type="button"
            onClick={this.reload}
            style={{
              display: 'inline-flex',
              alignItems: 'center',
              height: '40px',
              padding: '0 20px',
              borderRadius: '6px',
              border: 'none',
              background: '#fafafa',
              color: '#09090b',
              fontSize: '14px',
              fontWeight: 500,
              cursor: 'pointer',
            }}
          >
            {t('errors.boundary_reload', 'Reload the page')}
          </button>
          {isDev && (
            <pre
              style={{
                marginTop: '24px',
                padding: '12px',
                textAlign: 'left',
                overflow: 'auto',
                maxHeight: '240px',
                borderRadius: '6px',
                background: '#18181b',
                color: '#f87171',
                fontSize: '12px',
                whiteSpace: 'pre-wrap',
              }}
            >
              {error.message}
              {error.stack ? `\n\n${error.stack}` : ''}
            </pre>
          )}
        </div>
      </div>
    );
  }
}

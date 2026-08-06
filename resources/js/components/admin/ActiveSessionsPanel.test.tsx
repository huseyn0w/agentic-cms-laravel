import { render, screen, fireEvent } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

const { del, post, setData } = vi.hoisted(() => ({ del: vi.fn(), post: vi.fn(), setData: vi.fn() }));
vi.mock('@inertiajs/react', () => ({
  router: { delete: del },
  useForm: (initial: any) => ({ data: initial, errors: {}, processing: false, setData, post }),
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));

import { ActiveSessionsPanel, type SessionRow } from './ActiveSessionsPanel';

const rows: SessionRow[] = [
  { id: 'cur', ip: '127.0.0.1', device: 'Chrome · macOS', last_active: 'just now', is_current: true },
  { id: 'old', ip: '10.0.0.9', device: 'Firefox · Windows', last_active: '2 days ago', is_current: false },
];

describe('ActiveSessionsPanel', () => {
  it('renders a row per session and flags the current device', () => {
    render(<ActiveSessionsPanel sessions={rows} />);
    expect(screen.getAllByTestId('session-row')).toHaveLength(2);
    expect(screen.getByTestId('session-current')).toBeInTheDocument();
    expect(screen.getByText('Chrome · macOS')).toBeInTheDocument();
    expect(screen.getByText('Firefox · Windows')).toBeInTheDocument();
  });

  it('offers revoke only for non-current sessions and calls router.delete', () => {
    render(<ActiveSessionsPanel sessions={rows} />);
    // The current session has no revoke button.
    expect(screen.queryByTestId('session-revoke-cur')).not.toBeInTheDocument();

    fireEvent.click(screen.getByTestId('session-revoke-old'));
    expect(del).toHaveBeenCalledWith(
      '/agentic-cms-laravel-admin/myprofile/sessions/old',
      expect.objectContaining({ preserveScroll: true }),
    );
  });

  it('hides the log-out-others form when there are no other sessions', () => {
    render(<ActiveSessionsPanel sessions={[rows[0]]} />);
    expect(screen.queryByTestId('sessions-logout-others')).not.toBeInTheDocument();
  });
});

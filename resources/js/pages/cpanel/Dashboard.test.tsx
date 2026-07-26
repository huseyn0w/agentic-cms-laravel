import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import Dashboard from './Dashboard';

vi.mock('@inertiajs/react', () => ({ Head: () => null }));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));

describe('Dashboard page', () => {
  it('renders the three latest-N cards', () => {
    render(<Dashboard
      posts={[{ id: 1, title: 'Hello world' }]}
      users={[{ username: 'ada' }]}
      comments={[{ comment: 'Nice' }]} />);
    expect(screen.getByText('Hello world')).toBeInTheDocument();
    expect(screen.getByText('ada')).toBeInTheDocument();
    expect(screen.getByText('Nice')).toBeInTheDocument();
  });
});

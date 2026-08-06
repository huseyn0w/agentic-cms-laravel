import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { StatusPill } from './StatusPill';

describe('StatusPill', () => {
  it('renders the supplied label', () => {
    render(<StatusPill tone="success" label="Published" />);
    expect(screen.getByText('Published')).toBeInTheDocument();
  });

  it('maps each tone to its token classes', () => {
    const { rerender } = render(<StatusPill tone="success" label="Published" />);
    expect(screen.getByText('Published').closest('span')).toHaveClass('text-success');

    rerender(<StatusPill tone="warning" label="Pending" />);
    expect(screen.getByText('Pending').closest('span')).toHaveClass('text-warning');

    rerender(<StatusPill tone="muted" label="Private" />);
    expect(screen.getByText('Private').closest('span')).toHaveClass('text-subtle');
  });
});

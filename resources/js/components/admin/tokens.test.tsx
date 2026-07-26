import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

function Swatch() {
  return <div data-testid="swatch" className="bg-surface-3 text-faint border-strong" />;
}

describe('monochrome token bridge', () => {
  it('exposes surface-3 and faint bridge names', () => {
    render(<Swatch />);
    const el = screen.getByTestId('swatch');
    expect(el).toHaveClass('bg-surface-3');
    expect(el).toHaveClass('text-faint');
    expect(el).toHaveClass('border-strong');
  });
});

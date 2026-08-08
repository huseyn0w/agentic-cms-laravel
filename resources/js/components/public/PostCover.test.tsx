import { describe, it, expect } from 'vitest';
import { render } from '@testing-library/react';
import { PostCover } from './PostCover';

describe('PostCover', () => {
  it('renders an svg cover', () => {
    const { container } = render(<PostCover seed="hello-world" />);
    expect(container.querySelector('svg')).toBeTruthy();
  });

  it('is deterministic for the same seed and differs across seeds', () => {
    const a = render(<PostCover seed="alpha" />).container.innerHTML;
    const b = render(<PostCover seed="alpha" />).container.innerHTML;
    const c = render(<PostCover seed="beta" />).container.innerHTML;
    expect(a).toBe(b);
    expect(a).not.toBe(c);
  });
});

import { describe, it, expect } from 'vitest';
import { fireEvent, render } from '@testing-library/react';
import { PostImage } from './PostImage';

describe('PostImage', () => {
  it('renders the thumbnail img when a src is given', () => {
    const { container } = render(
      <PostImage thumbnail="https://cdn.test/a.jpg" coverSeed="a" title="A" />,
    );
    const img = container.querySelector('img');
    expect(img).toBeTruthy();
    expect(img?.getAttribute('src')).toBe('https://cdn.test/a.jpg');
    expect(container.querySelector('svg')).toBeNull();
  });

  it('renders the generated cover when thumbnail is null', () => {
    const { container } = render(<PostImage thumbnail={null} coverSeed="a" title="A" />);
    expect(container.querySelector('img')).toBeNull();
    expect(container.querySelector('svg')).toBeTruthy();
  });

  it('falls back to the generated cover when the thumbnail fails to load', () => {
    const { container } = render(
      <PostImage thumbnail="https://cdn.test/missing.jpg" coverSeed="a" title="A" />,
    );
    const img = container.querySelector('img');
    expect(img).toBeTruthy();
    fireEvent.error(img!);
    expect(container.querySelector('img')).toBeNull();
    expect(container.querySelector('svg')).toBeTruthy();
  });
});

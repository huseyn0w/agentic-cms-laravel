import { describe, it, expect } from 'vitest';
import { fireEvent, render } from '@testing-library/react';
import { Avatar } from './Avatar';

describe('Avatar', () => {
  it('renders the photo when a src is given', () => {
    const { container } = render(<Avatar src="https://cdn.test/a.jpg" name="Ada Lovelace" />);
    const img = container.querySelector('img');
    expect(img?.getAttribute('src')).toBe('https://cdn.test/a.jpg');
    expect(container.querySelector('svg')).toBeNull();
  });

  it('renders an initials cover when src is null', () => {
    const { container } = render(<Avatar src={null} name="Ada Lovelace" />);
    expect(container.querySelector('img')).toBeNull();
    const svg = container.querySelector('svg');
    expect(svg).toBeTruthy();
    expect(svg?.textContent).toBe('AL');
  });

  it('falls back to the initials cover when the photo fails to load', () => {
    const { container } = render(<Avatar src="https://cdn.test/missing.jpg" name="Grace Hopper" />);
    fireEvent.error(container.querySelector('img')!);
    expect(container.querySelector('img')).toBeNull();
    expect(container.querySelector('svg')?.textContent).toBe('GH');
  });

  it('uses a question mark when the name is empty', () => {
    const { container } = render(<Avatar src={null} name="   " />);
    expect(container.querySelector('svg')?.textContent).toBe('?');
  });
});

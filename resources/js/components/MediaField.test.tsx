import { render, screen, fireEvent } from '@testing-library/react';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import { MediaField } from './MediaField';

const openMock = vi.fn();

vi.mock('@/lib/lfm', () => ({
  useLfmPicker: () => ({ open: openMock }),
}));

describe('MediaField', () => {
  beforeEach(() => {
    openMock.mockClear();
  });

  it('renders a preview image when value is set', () => {
    render(<MediaField value="/storage/x.jpg" onChange={vi.fn()} />);
    const img = screen.getByRole('img');
    expect(img).toHaveAttribute('src', '/storage/x.jpg');
  });

  it('renders a placeholder when value is empty', () => {
    render(<MediaField value="" onChange={vi.fn()} />);
    expect(screen.queryByRole('img')).not.toBeInTheDocument();
    expect(screen.getByTestId('media-field-placeholder')).toBeInTheDocument();
  });

  it('calls the picker when "Choose image" is clicked', () => {
    render(<MediaField value="" onChange={vi.fn()} />);
    fireEvent.click(screen.getByRole('button', { name: /choose image/i }));
    expect(openMock).toHaveBeenCalledWith('Images');
  });

  it('calls onChange with an empty string when "Remove" is clicked', () => {
    const onChange = vi.fn();
    render(<MediaField value="/storage/x.jpg" onChange={onChange} />);
    fireEvent.click(screen.getByRole('button', { name: /remove/i }));
    expect(onChange).toHaveBeenCalledWith('');
  });

  it('does not render a "Remove" button when value is empty', () => {
    render(<MediaField value="" onChange={vi.fn()} />);
    expect(screen.queryByRole('button', { name: /remove/i })).not.toBeInTheDocument();
  });
});

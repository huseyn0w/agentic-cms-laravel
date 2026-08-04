import { render, screen, fireEvent } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

const post = vi.fn();
const put = vi.fn();
const setData = vi.fn();
vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  Link: ({ children, ...p }: any) => <a {...p}>{children}</a>,
  useForm: (initial: any) => ({ data: initial, errors: {}, processing: false, setData, post, put }),
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
import Form from './Form';

const permission_options = [
  { name: 'manage_posts', label: 'manage posts' },
  { name: 'manage_pages', label: 'manage pages' },
  { name: 'manage_users', label: 'manage users' },
];
const props = (over = {}) => ({ entity: null, permission_options, ...over });

describe('Roles Form', () => {
  it('new: renders name field + a checkbox per permission, POSTs to /roles/new', () => {
    render(<Form {...props()} />);
    expect(screen.getByTestId('role-name')).toBeInTheDocument();
    expect(screen.getByLabelText('perm-manage_posts')).toBeInTheDocument();
    expect(screen.getByLabelText('perm-manage_users')).toBeInTheDocument();

    fireEvent.submit(screen.getByTestId('role-name').closest('form')!);
    expect(post).toHaveBeenCalledWith('/agentic-cms-laravel-admin/roles/new');
  });

  it('edit: prefills the name and checks enabled permissions, PUTs to /{id}/update', () => {
    const entity = { id: 5, name: 'Author', permissions: ['manage_posts'] };
    render(<Form {...props({ entity })} />);
    expect(screen.getByTestId('role-name')).toHaveValue('Author');
    expect(screen.getByLabelText('perm-manage_posts')).toBeChecked();
    expect(screen.getByLabelText('perm-manage_pages')).not.toBeChecked();

    fireEvent.submit(screen.getByTestId('role-name').closest('form')!);
    expect(put).toHaveBeenCalledWith('/agentic-cms-laravel-admin/roles/5/update');
  });
});

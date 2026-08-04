import { render, screen, fireEvent } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

const post = vi.fn();
const put = vi.fn();
const setData = vi.fn();
let can = { manage_users: true };
vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  Link: ({ children, ...p }: any) => <a {...p}>{children}</a>,
  usePage: () => ({ props: { auth: { can } } }),
  useForm: (initial: any) => ({ data: initial, errors: {}, processing: false, setData, post, put }),
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
vi.mock('@/components/MediaField', () => ({ MediaField: ({ label }: any) => <div data-testid="media-field">{label}</div> }));
import Form from './Form';

const countries = [{ name: 'Germany' }, { name: 'UK' }];
const user_roles = [{ id: 1, name: 'Administrator' }, { id: 2, name: 'Editor' }];

const entity = {
  id: 7, username: 'editor', email: 'e@x.io', name: 'Ed', surname: 'Itor',
  country: 'UK', city: 'London', about_me: 'hi', facebook_url: '', twitter_url: '',
  instagram_url: '', google_url: '', linkedin_url: '', xing_url: '', role_id: 2,
  gender: 'male', avatar: '',
};

const props = (over = {}) => ({ entity: null, countries, user_roles, ...over });

beforeEach(() => {
  post.mockClear();
  put.mockClear();
  can = { manage_users: true };
});

describe('Users Form', () => {
  it('new: username is editable, role select shown, submits POST /users/new', () => {
    render(<Form {...props()} />);
    expect(screen.getByTestId('user-username')).toBeInTheDocument();
    expect(screen.getByTestId('user-email')).toBeInTheDocument();
    expect(screen.getByLabelText('user-role')).toBeInTheDocument();
    expect(screen.getByTestId('media-field')).toBeInTheDocument();

    fireEvent.submit(screen.getByTestId('user-email').closest('form')!);
    expect(post).toHaveBeenCalledWith('/agentic-cms-laravel-admin/users/new');
  });

  it('edit: username shown read-only (no input), fields prefilled, PUT /users/{id}/update', () => {
    render(<Form {...props({ entity })} />);
    expect(screen.queryByTestId('user-username')).not.toBeInTheDocument();
    expect(screen.getByText('editor')).toBeInTheDocument();
    expect(screen.getByTestId('user-email')).toHaveValue('e@x.io');

    fireEvent.submit(screen.getByTestId('user-email').closest('form')!);
    expect(put).toHaveBeenCalledWith('/agentic-cms-laravel-admin/users/7/update');
  });

  it('hides the role select when the viewer lacks manage_users (own profile)', () => {
    can = { manage_users: false };
    render(<Form {...props({ entity })} />);
    expect(screen.queryByLabelText('user-role')).not.toBeInTheDocument();
  });
});

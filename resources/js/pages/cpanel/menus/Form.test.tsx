import { render, screen, fireEvent, within } from '@testing-library/react';
import { describe, expect, it, vi, beforeEach } from 'vitest';

let transformer = (d: any) => d;
const post = vi.fn();
const put = vi.fn();
vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  Link: ({ children, ...p }: any) => <a {...p}>{children}</a>,
  useForm: (initial: any) => ({
    data: initial,
    errors: {},
    processing: false,
    setData: (k: string, v: any) => { initial[k] = v; },
    transform: (fn: any) => { transformer = fn; },
    post: (url: string, opts: any) => post(url, transformer({ ...initial }), opts),
    put: (url: string, opts: any) => put(url, transformer({ ...initial }), opts),
  }),
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
import Form from './Form';

const terms_list = {
  posts: [{ title: 'First Post', slug: 'first-post' }],
  pages: [{ title: 'Home', slug: '/' }, { title: 'About', slug: 'about' }],
  categories: [{ title: 'News', slug: 'news' }],
};
const props = (over = {}) => ({ entity: null, terms_list, translation_links: {}, ...over });

beforeEach(() => {
  post.mockClear();
  put.mockClear();
  transformer = (d) => d;
});

function selectOption(select: HTMLSelectElement, label: string) {
  const opt = within(select).getByRole('option', { name: label }) as HTMLOptionElement;
  opt.selected = true;
  fireEvent.change(select);
}

describe('Menu builder Form', () => {
  it('new: renders name/slug + source panel + empty canvas, posts serialized content', () => {
    render(<Form {...props()} />);
    expect(screen.getByTestId('menu-title')).toBeInTheDocument();
    expect(screen.getByTestId('menu-slug')).toBeInTheDocument();
    expect(screen.getByLabelText('source-pages')).toBeInTheDocument();
    expect(screen.getByTestId('menu-reorder-live')).toBeInTheDocument();

    fireEvent.submit(screen.getByTestId('menu-title').closest('form')!);
    expect(post).toHaveBeenCalled();
    const [url, data] = post.mock.calls[0];
    expect(url).toBe('/agentic-cms-laravel-admin/menus/new');
    expect(JSON.parse(data.content)).toEqual([]);
  });

  it('adds a selected page to the canvas as a typed item', () => {
    render(<Form {...props()} />);
    selectOption(screen.getByLabelText('source-pages') as HTMLSelectElement, 'About');
    fireEvent.click(screen.getByTestId('add-to-menu'));

    const canvas = screen.getByTestId('menu-canvas');
    expect(within(canvas).getByText('About')).toBeInTheDocument();

    fireEvent.submit(screen.getByTestId('menu-title').closest('form')!);
    const items = JSON.parse(post.mock.calls[0][1].content);
    expect(items).toEqual([{ title: 'About', slug: 'about', type: 'pages' }]);
  });

  it('adds a custom link', () => {
    render(<Form {...props()} />);
    fireEvent.change(screen.getByTestId('link-label'), { target: { value: 'Docs' } });
    fireEvent.change(screen.getByTestId('link-url'), { target: { value: 'https://docs.example' } });
    fireEvent.click(screen.getByTestId('add-to-menu'));

    fireEvent.submit(screen.getByTestId('menu-title').closest('form')!);
    const items = JSON.parse(post.mock.calls[0][1].content);
    expect(items).toEqual([{ title: 'Docs', slug: 'https://docs.example', type: 'custom_link' }]);
  });

  it('reorders items with move up and removes them', () => {
    const entity = {
      id: 4, title: 'Header', slug: 'header',
      items: [
        { title: 'Home', slug: '/', type: 'pages' },
        { title: 'Contact', slug: 'contact', type: 'pages' },
      ],
    };
    render(<Form {...props({ entity })} />);
    expect(screen.getByTestId('menu-title')).toHaveValue('Header');

    // Move "Contact" up above "Home".
    const contactRow = screen.getByText('Contact').closest('[data-testid="menu-item"]')!;
    fireEvent.click(within(contactRow as HTMLElement).getByLabelText('move-up'));

    fireEvent.submit(screen.getByTestId('menu-title').closest('form')!);
    const items = JSON.parse(put.mock.calls[0][1].content);
    expect(items.map((i: any) => i.slug)).toEqual(['contact', '/']);
    expect(put.mock.calls[0][0]).toBe('/agentic-cms-laravel-admin/menus/4/update');
  });
});

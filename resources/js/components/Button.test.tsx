import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { Button } from './Button';

describe('Button', () => {
    it('renders its label and passes data-testid', () => {
        render(<Button data-testid="login-submit">Sign in</Button>);
        const btn = screen.getByTestId('login-submit');
        expect(btn).toHaveTextContent('Sign in');
        expect(btn.tagName).toBe('BUTTON');
    });

    it('renders an anchor when href is set', () => {
        render(<Button href="/x">Go</Button>);
        expect(screen.getByRole('link', { name: 'Go' })).toHaveAttribute('href', '/x');
    });

    it('marks aria-busy when loading', () => {
        render(<Button loading>Save</Button>);
        expect(screen.getByRole('button')).toHaveAttribute('aria-busy', 'true');
    });
});

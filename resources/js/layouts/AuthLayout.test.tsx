import { render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import { AuthLayout } from './AuthLayout';

describe('AuthLayout', () => {
    beforeEach(() => {
        localStorage.clear();
        document.documentElement.classList.remove('dark');
    });
    afterEach(() => localStorage.clear());

    it('renders the brand wordmark and the children', () => {
        render(
            <AuthLayout title="Welcome back">
                <p>form here</p>
            </AuthLayout>,
        );
        expect(screen.getByText('Agentic CMS')).toBeInTheDocument();
        expect(screen.getByText('Welcome back')).toBeInTheDocument();
        expect(screen.getByText('form here')).toBeInTheDocument();
    });

    it('applies the stored dark theme to <html>', () => {
        localStorage.setItem('agentic-cms-theme', 'dark');
        render(<AuthLayout title="x">y</AuthLayout>);
        expect(document.documentElement.classList.contains('dark')).toBe(true);
    });
});

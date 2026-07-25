import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { GoogleButton } from './GoogleButton';

describe('GoogleButton', () => {
    it('links to the google oauth entrypoint', () => {
        render(<GoogleButton>Continue with Google</GoogleButton>);
        expect(screen.getByRole('link', { name: /google/i })).toHaveAttribute('href', '/login/google');
    });
});

import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { TextField } from './TextField';

describe('TextField', () => {
    it('renders a label bound to the input', () => {
        render(<TextField name="email" label="Email" />);
        expect(screen.getByLabelText('Email')).toHaveAttribute('name', 'email');
    });

    it('renders an error with role=alert and wires aria-describedby', () => {
        render(<TextField name="email" label="Email" error="Required" />);
        expect(screen.getByRole('alert')).toHaveTextContent('Required');
        expect(screen.getByLabelText('Email')).toHaveAttribute('aria-invalid', 'true');
    });
});

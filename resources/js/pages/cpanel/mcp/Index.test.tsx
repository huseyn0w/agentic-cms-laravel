import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import Index from './Index';

vi.mock('@inertiajs/react', () => ({
  Head: () => null,
}));

describe('cpanel MCP Index', () => {
  it('shows the server endpoint and OAuth discovery URL', () => {
    render(<Index endpoint="https://site.test/mcp/agentic-cms-laravel" discoveryUrl="https://site.test/.well-known/oauth-protected-resource" />);
    expect(screen.getByText('https://site.test/mcp/agentic-cms-laravel')).toBeInTheDocument();
    expect(screen.getByText('https://site.test/.well-known/oauth-protected-resource')).toBeInTheDocument();
  });

  it('lists connection steps and copy controls', () => {
    render(<Index endpoint="https://site.test/mcp/agentic-cms-laravel" discoveryUrl="https://site.test/.well-known/oauth-protected-resource" />);
    expect(screen.getByText('How to connect')).toBeInTheDocument();
    expect(screen.getAllByText('Copy').length).toBeGreaterThanOrEqual(2);
  });
});

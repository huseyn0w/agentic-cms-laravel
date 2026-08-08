<?php

namespace App\Http\Controllers\CPanel;

use Inertia\Inertia;

/**
 * MCP connection guide. The AgenticCms-Laravel MCP server authenticates clients
 * with OAuth 2.1 (dynamic client registration via Mcp::oauthRoutes()), so this
 * screen does not mint tokens — an MCP client discovers auth from the endpoint
 * and registers itself. It just surfaces the URLs and connection steps.
 */
class CPanelMcpController extends CPanelBaseController
{
    public function index()
    {
        return Inertia::render('cpanel/mcp/Index', [
            'endpoint' => url('/mcp/agentic-cms-laravel'),
            'discoveryUrl' => url('/.well-known/oauth-protected-resource'),
        ]);
    }
}

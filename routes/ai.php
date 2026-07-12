<?php

use App\Mcp\Servers\AgenticCmsLaravelServer;
use Laravel\Mcp\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| AI / MCP Routes
|--------------------------------------------------------------------------
|
| The AgenticCms-Laravel MCP server lets an authenticated AI client (e.g. Claude)
| manage content, users, settings and theme templates. Access is protected
| with OAuth 2.1 via Laravel Passport: oauthRoutes() advertises the discovery
| and dynamic client-registration endpoints the MCP spec expects, and the
| `auth:api` (Passport) guard authenticates every request. Per-tool admin
| permissions are then enforced inside each tool.
|
| Endpoint: POST /mcp/agentic-cms-laravel
|
*/

Mcp::oauthRoutes();

Mcp::web('/mcp/agentic-cms-laravel', AgenticCmsLaravelServer::class)
    ->middleware(['auth:api', 'throttle:120,1']);

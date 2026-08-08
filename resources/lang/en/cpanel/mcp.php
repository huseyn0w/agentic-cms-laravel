<?php

/**
 * AgenticCms-Laravel
 * File: mcp.php
 * Strings for the admin MCP connection guide (cpanel/mcp/Index).
 */

return [

    'title' => 'MCP connection',
    'subtitle' => 'Connect an AI assistant to manage this site through the built-in MCP server.',
    'endpoint_label' => 'Server endpoint',
    'discovery_label' => 'OAuth discovery URL',
    'copy' => 'Copy',
    'copied' => 'Copied',
    'steps_heading' => 'How to connect',
    'step_1' => 'Open your MCP client (e.g. Claude) and add a custom connector.',
    'step_2' => 'Paste the server URL below as the connector endpoint.',
    'step_3' => 'When prompted, authorize access and sign in with your admin account.',
    'step_4' => 'The client registers itself over OAuth 2.1 — no manual token needed.',
    'oauth_note' => 'Access is protected by OAuth 2.1. Each tool still enforces your admin permissions, so a connected client can only do what your account is allowed to.',

];

<?php

namespace App\Mcp\Concerns;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

/**
 * Per-tool authorization for the AgenticCms-Laravel MCP server.
 *
 * Authentication (who the caller is) is handled by the OAuth `auth:api`
 * middleware on the route. This trait handles *authorization* (what that
 * caller may do) by reading the same permission flags the admin panel uses.
 *
 * It deliberately reads permissions off the token-authenticated user passed in
 * the MCP Request rather than off Auth::user()/the session, so a tool can never
 * be tricked into honouring an ambient web session instead of the bearer token.
 */
trait AuthorizesAccess
{
    /**
     * Return an error Response when the authenticated user lacks $permission,
     * or null when the call is allowed to proceed.
     *
     * Usage at the top of handle():
     *
     *     if ($denied = $this->deny($request, 'manage_posts')) {
     *         return $denied;
     *     }
     */
    protected function deny(Request $request, string $permission): ?Response
    {
        $user = $request->user();

        if (is_null($user)) {
            return Response::error('Authentication required. Connect with a valid OAuth token.');
        }

        if (! $this->userHasPermission($user, $permission)) {
            return Response::error(
                "Permission denied. This action requires the '{$permission}' capability, "
                .'which the authenticated account does not hold.'
            );
        }

        return null;
    }

    /**
     * Whether the user's role grants the given permission flag.
     *
     * Mirrors UserPolicy: permissions() returns the role's JSON flag map, e.g.
     * {"manage_posts":1,"manage_users":0,...}; a capability is granted only
     * when its flag is exactly 1.
     */
    protected function userHasPermission(object $user, string $permission): bool
    {
        $decoded = json_decode($user->permissions() ?? '', true);

        if (! is_array($decoded)) {
            return false;
        }

        return ($decoded[$permission] ?? 0) === 1;
    }
}

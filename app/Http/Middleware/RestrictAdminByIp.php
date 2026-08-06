<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts the admin panel to an allowlist of IPs / CIDR ranges from the
 * security_settings singleton (one entry per line). Empty = no restriction.
 *
 * Fails open when the list contains no valid entries (e.g. a typo-only list),
 * so an admin cannot lock everyone out with a malformed allowlist. Matching is
 * delegated to Symfony's IpUtils (single IPs + CIDR, IPv4/IPv6).
 */
class RestrictAdminByIp
{
    public function handle(Request $request, Closure $next): Response
    {
        $entries = $this->validEntries((string) get_security_settings('admin_ip_allowlist'));

        // No restriction configured (empty or all-invalid) -> allow.
        if ($entries === []) {
            return $next($request);
        }

        if (! IpUtils::checkIp((string) $request->ip(), $entries)) {
            abort(403, trans('cpanel/security.ip_forbidden'));
        }

        return $next($request);
    }

    /**
     * Parse the textarea into trimmed, non-empty, syntactically-valid entries
     * (a bare IP or a CIDR). Invalid tokens are dropped.
     *
     * @return array<int, string>
     */
    private function validEntries(string $raw): array
    {
        $entries = [];

        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            [$ip] = array_pad(explode('/', $line, 2), 1, null);
            if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                $entries[] = $line;
            }
        }

        return $entries;
    }
}

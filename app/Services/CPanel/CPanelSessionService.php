<?php

namespace App\Services\CPanel;

use App\Repositories\CPanelSessionRepository;
use Carbon\Carbon;

/**
 * Presents and revokes a user's active browser sessions from the database
 * session store. All data access goes through CPanelSessionRepository; this
 * service only shapes rows for the UI and parses the user-agent into a short
 * "Browser · OS" label (own minimal parser, no dependency).
 */
class CPanelSessionService
{
    public function __construct(private CPanelSessionRepository $repo) {}

    /**
     * The user's active sessions shaped for the profile panel, with the current
     * session flagged and floated to the top.
     *
     * @return array<int, array{id: string, ip: string|null, device: string, last_active: string, is_current: bool}>
     */
    public function activeSessions(int $userId, string $currentId): array
    {
        return $this->repo->forUser($userId)
            ->map(fn ($row) => [
                'id' => (string) $row->id,
                'ip' => $row->ip_address,
                'device' => $this->device($row->user_agent),
                'last_active' => Carbon::createFromTimestamp((int) $row->last_activity)->diffForHumans(),
                'is_current' => (string) $row->id === $currentId,
            ])
            ->sortByDesc('is_current')
            ->values()
            ->all();
    }

    public function revoke(int $userId, string $sessionId): int
    {
        return $this->repo->revoke($userId, $sessionId);
    }

    public function revokeOthers(int $userId, string $currentId): int
    {
        return $this->repo->revokeOthers($userId, $currentId);
    }

    /**
     * A short, human "Browser · OS" label from a user-agent string. Order
     * matters: Chrome/Edge/Opera UAs also contain "Safari", so they are matched
     * first.
     */
    private function device(?string $userAgent): string
    {
        $ua = (string) $userAgent;

        $browser = match (true) {
            str_contains($ua, 'Edg') => 'Edge',
            str_contains($ua, 'OPR'), str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Chrome') => 'Chrome',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Safari') => 'Safari',
            default => 'Unknown browser',
        };

        $os = match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS'), str_contains($ua, 'Macintosh') => 'macOS',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone'), str_contains($ua, 'iPad'), str_contains($ua, 'iOS') => 'iOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'Unknown OS',
        };

        return $browser.' · '.$os;
    }
}

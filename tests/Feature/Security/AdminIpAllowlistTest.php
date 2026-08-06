<?php

namespace Tests\Feature\Security;

use App\Http\Models\CPanel\CPanelSecuritySettings;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin-panel IP allowlist: when set, only matching IPs (single or CIDR)
 * may reach the admin routes; an all-invalid list fails open (no lockout).
 * Empty = no restriction. Gated on the admin route group.
 */
class AdminIpAllowlistTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN = '/agentic-cms-laravel-admin';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config(['inertia.testing.ensure_pages_exist' => false]);
    }

    private function allowlist(?string $value): void
    {
        CPanelSecuritySettings::firstOrNew(['id' => 1])
            ->forceFill(['admin_ip_allowlist' => $value])->save();
    }

    private function admin(): User
    {
        return User::where('username', 'admin')->firstOrFail();
    }

    /** @return array<string, string> server vars carrying the client IP */
    private function fromIp(string $ip): array
    {
        return ['REMOTE_ADDR' => $ip];
    }

    public function test_empty_allowlist_allows_any_ip(): void
    {
        $this->allowlist(null);
        $this->actingAs($this->admin())
            ->withServerVariables($this->fromIp('203.0.113.9'))
            ->get(self::ADMIN)->assertOk();
    }

    public function test_ip_not_in_allowlist_is_forbidden(): void
    {
        $this->allowlist("198.51.100.5\n10.0.0.0/8");
        $this->actingAs($this->admin())
            ->withServerVariables($this->fromIp('203.0.113.9'))
            ->get(self::ADMIN)->assertForbidden();
    }

    public function test_listed_single_ip_is_allowed(): void
    {
        $this->allowlist("198.51.100.5\n10.0.0.0/8");
        $this->actingAs($this->admin())
            ->withServerVariables($this->fromIp('198.51.100.5'))
            ->get(self::ADMIN)->assertOk();
    }

    public function test_ip_within_listed_cidr_is_allowed(): void
    {
        $this->allowlist('10.0.0.0/8');
        $this->actingAs($this->admin())
            ->withServerVariables($this->fromIp('10.20.30.40'))
            ->get(self::ADMIN)->assertOk();
    }

    public function test_all_invalid_entries_fail_open(): void
    {
        $this->allowlist("not-an-ip\nalso bad");
        $this->actingAs($this->admin())
            ->withServerVariables($this->fromIp('203.0.113.9'))
            ->get(self::ADMIN)->assertOk();
    }
}

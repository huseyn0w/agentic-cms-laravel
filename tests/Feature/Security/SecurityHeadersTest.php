<?php

namespace Tests\Feature\Security;

use App\Http\Models\CPanel\CPanelSecuritySettings;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The SecurityHeaders middleware always sends the baseline hardening headers,
 * and emits HSTS / CSP only when the admin opts in via security_settings.
 * Exercised against /health (web group, no seeding needed for the response).
 */
class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function settings(array $overrides): void
    {
        CPanelSecuritySettings::firstOrNew(['id' => 1])->fill($overrides)->save();
    }

    public function test_baseline_headers_are_always_present(): void
    {
        $res = $this->get('/health');

        $res->assertHeader('X-Content-Type-Options', 'nosniff');
        $res->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $res->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertNotNull($res->headers->get('Permissions-Policy'));
    }

    public function test_hsts_absent_by_default_and_present_when_enabled_over_https(): void
    {
        $this->get('https://localhost/health')->assertHeaderMissing('Strict-Transport-Security');

        $this->settings(['hsts_enabled' => true, 'hsts_max_age' => 1000]);

        $res = $this->get('https://localhost/health');
        $this->assertStringContainsString('max-age=1000', $res->headers->get('Strict-Transport-Security'));
    }

    public function test_hsts_not_sent_over_plain_http_even_when_enabled(): void
    {
        $this->settings(['hsts_enabled' => true]);
        $this->get('http://localhost/health')->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_csp_is_sent_when_configured(): void
    {
        $this->settings(['csp' => "default-src 'self'"]);
        $this->get('/health')->assertHeader('Content-Security-Policy', "default-src 'self'");
    }

    public function test_csp_report_only_uses_the_report_only_header(): void
    {
        $this->settings(['csp' => "default-src 'self'", 'csp_report_only' => true]);

        $res = $this->get('/health');
        $res->assertHeader('Content-Security-Policy-Report-Only', "default-src 'self'");
        $res->assertHeaderMissing('Content-Security-Policy');
    }
}

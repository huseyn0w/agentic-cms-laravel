<?php

namespace Tests\Unit;

use App\Services\Auth\TwoFactorService;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorServiceTest extends TestCase
{
    private TwoFactorService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new TwoFactorService(new Google2FA);
    }

    public function test_generates_a_non_empty_base32_secret(): void
    {
        $secret = $this->svc->generateSecret();
        $this->assertNotEmpty($secret);
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
    }

    public function test_verify_accepts_the_current_code_and_rejects_a_wrong_one(): void
    {
        $secret = $this->svc->generateSecret();
        $valid = (new Google2FA)->getCurrentOtp($secret);

        $this->assertTrue($this->svc->verify($secret, $valid));
        $this->assertFalse($this->svc->verify($secret, '000000'));
        $this->assertFalse($this->svc->verify($secret, ''));
    }

    public function test_generate_recovery_codes_returns_eight_unique_formatted_codes(): void
    {
        $codes = $this->svc->generateRecoveryCodes();
        $this->assertCount(8, $codes);
        $this->assertCount(8, array_unique($codes));
        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{8}-[A-Za-z0-9]{8}$/', $code);
        }
    }

    public function test_qr_code_svg_contains_svg_markup(): void
    {
        $secret = $this->svc->generateSecret();
        $svg = $this->svc->qrCodeSvg('Agentic CMS', 'user@test.dev', $secret);
        $this->assertStringContainsString('<svg', $svg);
    }
}

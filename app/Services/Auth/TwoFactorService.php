<?php

namespace App\Services\Auth;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;

/**
 * Pure TOTP logic (RFC 6238) over pragmarx/google2fa + bacon/bacon-qr-code.
 * No DB access — persistence lives in CPanelUserRepository. This is the only
 * place that imports the 2FA libraries.
 */
class TwoFactorService
{
    public function __construct(private Google2FA $engine) {}

    public function generateSecret(): string
    {
        return $this->engine->generateSecretKey();
    }

    /**
     * Verify a submitted code against the secret with a ±1 time-step window.
     * Returns false on empty/malformed input rather than throwing.
     */
    public function verify(string $secret, string $code): bool
    {
        $code = trim($code);
        if ($secret === '' || $code === '') {
            return false;
        }

        try {
            return (bool) $this->engine->verifyKey($secret, $code, 1);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Render the otpauth:// URI as a self-contained SVG string (no imagick).
     */
    public function qrCodeSvg(string $company, string $holder, string $secret): string
    {
        $url = $this->engine->getQRCodeUrl($company, $holder, $secret);

        $writer = new Writer(new ImageRenderer(new RendererStyle(200, 0), new SvgImageBackEnd));

        return $writer->writeString($url);
    }

    /**
     * @return array<int, string> N single-use recovery codes, `xxxxxxxx-xxxxxxxx`.
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        while (count($codes) < $count) {
            $code = $this->segment().'-'.$this->segment();
            $codes[$code] = true; // dedupe by key
        }

        return array_keys($codes);
    }

    private function segment(): string
    {
        // hex is always exactly [0-9a-f], so an 8-char slice is guaranteed —
        // no risk of base64 padding/symbol removal shortening the segment.
        return substr(bin2hex(random_bytes(8)), 0, 8);
    }
}

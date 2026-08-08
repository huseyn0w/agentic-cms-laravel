<?php

namespace Tests\Unit\Support;

use App\Support\Updater\Signer;
use App\Support\Updater\Verifier;
use Tests\TestCase;

/**
 * Release integrity: a release signed with the Ed25519 secret key verifies with
 * the matching public key, and any tampering (content, signature, wrong key)
 * fails. This is the signature gate the production updater enforces.
 */
class SignerVerifierTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->file = sys_get_temp_dir().'/cms-sign-'.bin2hex(random_bytes(4)).'.bin';
        file_put_contents($this->file, 'release payload '.bin2hex(random_bytes(16)));
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
        @unlink($this->file.'.sig');
        parent::tearDown();
    }

    public function test_signed_archive_verifies_with_matching_public_key(): void
    {
        $keys = Signer::generateKeypair();

        (new Signer)->sign($this->file, $keys['secret']);

        $this->assertTrue((new Verifier)->verifyWithSidecar($this->file, $keys['public']));
    }

    public function test_tampered_archive_fails_verification(): void
    {
        $keys = Signer::generateKeypair();
        (new Signer)->sign($this->file, $keys['secret']);

        // Tamper with the payload after signing.
        file_put_contents($this->file, 'tampered');

        $this->assertFalse((new Verifier)->verifyWithSidecar($this->file, $keys['public']));
    }

    public function test_wrong_public_key_fails_verification(): void
    {
        $keys = Signer::generateKeypair();
        $other = Signer::generateKeypair();
        $sig = base64_encode(file_get_contents((new Signer)->sign($this->file, $keys['secret'])));

        // Signature file holds base64; read it back as the signature string.
        $signature = file_get_contents($this->file.'.sig');

        $this->assertTrue((new Verifier)->verify($this->file, $signature, $keys['public']));
        $this->assertFalse((new Verifier)->verify($this->file, $signature, $other['public']));
    }

    public function test_malformed_signature_returns_false_not_throw(): void
    {
        $keys = Signer::generateKeypair();

        $this->assertFalse((new Verifier)->verify($this->file, 'not-base64!!', $keys['public']));
        $this->assertFalse((new Verifier)->verifyWithSidecar($this->file, $keys['public']));
    }
}

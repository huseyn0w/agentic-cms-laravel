<?php

namespace App\Support\Updater;

use RuntimeException;

/**
 * Signs a release archive with an Ed25519 secret key (libsodium).
 *
 * Produces a detached signature written next to the archive as `<archive>.sig`
 * (base64). The CI release job runs this with CMS_RELEASE_SIGN_KEY; the updater
 * verifies it with the matching public key (Verifier) before applying — and in
 * production a valid signature is mandatory.
 *
 * Ed25519 via ext-sodium is used instead of shelling out to gpg/minisign so the
 * whole sign/verify path is dependency-free and testable.
 */
class Signer
{
    /**
     * Generate a fresh signing keypair.
     *
     * @return array{public: string, secret: string} base64-encoded keys
     */
    public static function generateKeypair(): array
    {
        $pair = sodium_crypto_sign_keypair();

        return [
            'public' => base64_encode(sodium_crypto_sign_publickey($pair)),
            'secret' => base64_encode(sodium_crypto_sign_secretkey($pair)),
        ];
    }

    /**
     * Sign $filePath with the base64 Ed25519 secret key. Writes `<file>.sig`
     * and returns its path.
     */
    public function sign(string $filePath, string $base64SecretKey): string
    {
        if (! is_file($filePath)) {
            throw new RuntimeException("Cannot sign missing file: {$filePath}");
        }

        $secret = base64_decode($base64SecretKey, true);
        if ($secret === false || strlen($secret) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            throw new RuntimeException('Invalid signing secret key.');
        }

        $signature = sodium_crypto_sign_detached((string) file_get_contents($filePath), $secret);
        $sigPath = $filePath.'.sig';
        file_put_contents($sigPath, base64_encode($signature));

        return $sigPath;
    }
}

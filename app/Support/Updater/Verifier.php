<?php

namespace App\Support\Updater;

/**
 * Verifies a release archive against a detached Ed25519 signature (libsodium)
 * and the trusted public key from config('cms.update.public_key').
 *
 * The updater calls this before applying a release; in production an invalid or
 * missing signature aborts the update. Pairs with Signer.
 */
class Verifier
{
    /**
     * Verify $filePath against a base64 detached signature and base64 public key.
     * Returns false on any malformed input rather than throwing, so the updater
     * can treat "unverifiable" and "invalid" the same way (refuse).
     */
    public function verify(string $filePath, string $base64Signature, string $base64PublicKey): bool
    {
        if (! is_file($filePath)) {
            return false;
        }

        $signature = base64_decode($base64Signature, true);
        $public = base64_decode($base64PublicKey, true);

        if ($signature === false || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES) {
            return false;
        }

        if ($public === false || strlen($public) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            return false;
        }

        return sodium_crypto_sign_verify_detached(
            $signature,
            (string) file_get_contents($filePath),
            $public
        );
    }

    /**
     * Convenience: read the signature from a `<file>.sig` (base64) file.
     */
    public function verifyWithSidecar(string $filePath, string $base64PublicKey): bool
    {
        $sigPath = $filePath.'.sig';
        if (! is_file($sigPath)) {
            return false;
        }

        return $this->verify($filePath, (string) file_get_contents($sigPath), $base64PublicKey);
    }
}

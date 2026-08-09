<?php

namespace Tests\Feature\Updater;

use App\Support\Updater\Signer;
use App\Support\Updater\Verifier;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * cms:generate-keys prints a usable Ed25519 keypair: the secret signs a release
 * and the printed public key verifies that signature. This is the one-time
 * setup for the CMS_RELEASE_SIGN_KEY / CMS_UPDATE_PUBLIC_KEY pair.
 */
class GenerateSigningKeysCommandTest extends TestCase
{
    public function test_the_command_is_registered(): void
    {
        $this->assertArrayHasKey('cms:generate-keys', Artisan::all());
    }

    public function test_the_printed_keys_sign_and_verify_a_file(): void
    {
        Artisan::call('cms:generate-keys');
        $output = Artisan::output();

        // The base64 keys are the two non-comment lines.
        $keys = array_values(array_filter(
            array_map('trim', explode("\n", $output)),
            fn ($line) => $line !== '' && ! str_starts_with($line, '#')
        ));
        $this->assertCount(2, $keys);
        [$secret, $public] = $keys;

        $file = sys_get_temp_dir().'/cms-keys-'.bin2hex(random_bytes(4)).'.bin';
        file_put_contents($file, 'release payload');

        $sigPath = (new Signer)->sign($file, $secret);
        $signature = (string) file_get_contents($sigPath);

        $this->assertTrue((new Verifier)->verify($file, $signature, $public));

        @unlink($file);
        @unlink($sigPath);
    }
}

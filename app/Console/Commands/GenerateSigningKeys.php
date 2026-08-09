<?php

namespace App\Console\Commands;

use App\Support\Updater\Signer;
use Illuminate\Console\Command;

/**
 * Print a fresh Ed25519 release signing keypair.
 *
 * The secret half goes into the CI secret CMS_RELEASE_SIGN_KEY (used by
 * cms:build-release to sign the archive). The public half goes into each
 * site's CMS_UPDATE_PUBLIC_KEY, which the updater trusts to verify releases.
 * Never put the secret key on a running site.
 */
class GenerateSigningKeys extends Command
{
    protected $signature = 'cms:generate-keys';

    protected $description = 'Generate an Ed25519 release signing keypair (base64)';

    public function handle(): int
    {
        $pair = Signer::generateKeypair();

        $this->line('# Secret key — CI secret CMS_RELEASE_SIGN_KEY (keep private, never on a site):');
        $this->line($pair['secret']);
        $this->newLine();
        $this->line('# Public key — site env CMS_UPDATE_PUBLIC_KEY:');
        $this->line($pair['public']);

        return self::SUCCESS;
    }
}

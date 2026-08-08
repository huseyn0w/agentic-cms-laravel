<?php

namespace App\Support\Updater;

use Symfony\Component\Process\Process;
use Throwable;

/**
 * Probes what the current host can do so the updater adapts instead of assuming
 * one environment. Local dev has everything; shared hosting (Hostinger) has no
 * Node and disables shell_exec/exec (only proc_open survives); a VPS has full
 * access. UpdateService reads these flags to choose a safe path — shipped
 * vendor/ vs `composer install`, prebuilt assets vs building on the host.
 */
class Environment
{
    /**
     * True when proc_open is available (not in disable_functions). This is the
     * one process primitive Hostinger leaves enabled; Symfony Process needs it.
     */
    public function hasProcOpen(): bool
    {
        if (! function_exists('proc_open')) {
            return false;
        }

        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        return ! in_array('proc_open', $disabled, true);
    }

    /**
     * True when a `composer` binary responds. Used to decide whether the
     * install_composer path is viable on this host.
     */
    public function hasComposer(): bool
    {
        return $this->commandSucceeds(['composer', '--version']);
    }

    /**
     * True when a `node` binary responds on PATH.
     */
    public function hasNode(): bool
    {
        return $this->commandSucceeds(['node', '--version']);
    }

    public function phpVersion(): string
    {
        return PHP_VERSION;
    }

    /**
     * True when $path exists and is writable (or, for a not-yet-existing path,
     * its parent is writable) — the updater must write into the install tree.
     */
    public function canWrite(string $path): bool
    {
        if (is_dir($path) || is_file($path)) {
            return is_writable($path);
        }

        $parent = dirname($path);

        return is_dir($parent) && is_writable($parent);
    }

    /**
     * @return array{proc_open: bool, composer: bool, node: bool, php_version: string}
     */
    public function summary(): array
    {
        return [
            'proc_open' => $this->hasProcOpen(),
            'composer' => $this->hasComposer(),
            'node' => $this->hasNode(),
            'php_version' => $this->phpVersion(),
        ];
    }

    /**
     * Run a probe command and report success. Safe on hosts with proc_open
     * disabled (returns false instead of throwing).
     *
     * @param  list<string>  $command
     */
    private function commandSucceeds(array $command): bool
    {
        if (! $this->hasProcOpen()) {
            return false;
        }

        try {
            $process = new Process($command);
            $process->setTimeout(10);
            $process->run();

            return $process->isSuccessful();
        } catch (Throwable) {
            return false;
        }
    }
}

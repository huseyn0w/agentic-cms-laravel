<?php

namespace App\Services\Seo;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

/**
 * Keeps the server-side renderer alive.
 *
 * The renderer is a plain Node process started by the deploy. That covers
 * everything except a server reboot, after which nothing brings it back and
 * pages quietly fall back to client rendering until the next deploy. The
 * scheduler calls this to close that gap, so the whole arrangement needs
 * nothing from the hosting panel: no Node app, no subdomain, and no publicly
 * reachable render endpoint.
 */
class SsrProcess
{
    /** Where CloudLinux keeps Node runtimes; never on PATH. */
    private const RUNTIME_GLOB = '/opt/alt/alt-nodejs*/root/usr/bin/node';

    public function isEnabled(): bool
    {
        return (bool) config('inertia.ssr.public.enabled', false);
    }

    public function isRunning(): bool
    {
        try {
            return Http::timeout(3)
                ->get(rtrim((string) config('inertia.ssr.url'), '/').'/health')
                ->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function bundlePath(): string
    {
        return base_path('bootstrap/ssr/ssr.js');
    }

    /** The newest Node available, or null when there is none. */
    public function nodeBinary(): ?string
    {
        $configured = config('inertia.ssr.public.node_binary');

        if (is_string($configured) && $configured !== '' && is_executable($configured)) {
            return $configured;
        }

        $runtimes = glob(self::RUNTIME_GLOB) ?: [];
        rsort($runtimes);

        foreach ($runtimes as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        // Hostinger disables shell_exec, exec and system; proc_open (which is
        // what the Process facade uses) is the one that survives.
        $onPath = trim(Process::run('command -v node')->output());

        return $onPath !== '' ? $onPath : null;
    }

    /**
     * Launch it, detached, so it outlives the process that asked for it.
     *
     * @return bool whether a start was attempted
     */
    public function start(): bool
    {
        $node = $this->nodeBinary();

        if ($node === null || ! is_file($this->bundlePath())) {
            return false;
        }

        if (! function_exists('proc_open')) {
            return false;
        }

        $log = storage_path('logs/ssr.log');

        // Started through proc_open with FILES on all three descriptors, not
        // pipes. This is the whole point of doing it by hand: a renderer
        // launched with pipes inherits one and never closes it, so whatever
        // read the launcher's output waits forever on a process designed to
        // run forever. That is a hung scheduler every five minutes.
        $handle = proc_open(
            sprintf('nohup %s bootstrap/ssr/ssr.js &', escapeshellarg($node)),
            [
                0 => ['file', '/dev/null', 'r'],
                1 => ['file', $log, 'a'],
                2 => ['file', $log, 'a'],
            ],
            $pipes,
            base_path(),
        );

        if (! is_resource($handle)) {
            return false;
        }

        // Closes the shell that did the backgrounding, not the renderer.
        proc_close($handle);

        return true;
    }
}

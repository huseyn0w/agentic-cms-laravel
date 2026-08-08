<?php

namespace App\Support;

use App\Plugins\Contracts\PluginInterface;
use App\Repositories\CPanelPluginRepository;
use Illuminate\Support\Str;

/**
 * Discovers in-repo plugins on the filesystem, syncs them into the plugins
 * table, and boots the enabled ones. Filesystem scanning is not data access; the
 * only DB access is delegated to CPanelPluginRepository.
 */
class PluginManager
{
    public function __construct(private CPanelPluginRepository $repository) {}

    /** @return array<string, PluginInterface> keyed by slug */
    public function discover(): array
    {
        $plugins = [];

        // Scan both the core plugin dir and the site-owned dir. The site zone
        // (app/Site) is never overwritten by a core update, so a fork can add
        // plugins there and keep them across updates.
        $files = [
            ...glob(app_path('Plugins/*/*Plugin.php')) ?: [],
            ...glob(app_path('Site/Plugins/*/*Plugin.php')) ?: [],
        ];

        foreach ($files as $file) {
            $class = $this->classFromFile($file);

            if ($class !== null && is_subclass_of($class, PluginInterface::class)) {
                /** @var PluginInterface $plugin */
                $plugin = new $class;
                $plugins[$plugin->slug()] = $plugin;
            }
        }

        return $plugins;
    }

    public function sync(): void
    {
        foreach (array_keys($this->discover()) as $slug) {
            $this->repository->ensureExists($slug);
        }
    }

    public function loadEnabled(Hooks $hooks): void
    {
        $discovered = $this->discover();

        foreach ($this->repository->enabledSlugs() as $slug) {
            if (isset($discovered[$slug])) {
                $discovered[$slug]->boot($hooks);
            }
        }
    }

    private function classFromFile(string $file): ?string
    {
        $relative = Str::of($file)
            ->after(app_path().DIRECTORY_SEPARATOR)
            ->replace(DIRECTORY_SEPARATOR, '\\')
            ->beforeLast('.php');

        $class = 'App\\'.$relative;

        return class_exists($class) ? $class : null;
    }
}

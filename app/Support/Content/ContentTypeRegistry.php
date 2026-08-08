<?php

namespace App\Support\Content;

use App\Repositories\CPanelPluginRepository;
use App\Support\PluginManager;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * The runtime registry of content types contributed by ENABLED plugins.
 *
 * Booted lazily on first read: it asks the PluginManager for discovered plugins,
 * filters to the enabled ones that implement RegistersContentTypes, and collects
 * their content types. Registered as a singleton so this happens once per
 * request. Disabling a plugin drops its type from the admin with no restart.
 */
class ContentTypeRegistry
{
    /** @var array<string, ContentType> */
    private array $types = [];

    private bool $booted = false;

    public function register(ContentType $type): void
    {
        $this->types[$type->slug] = $type;
    }

    /** @return array<string, ContentType> */
    public function all(): array
    {
        $this->ensureBooted();

        return $this->types;
    }

    public function get(string $slug): ?ContentType
    {
        $this->ensureBooted();

        return $this->types[$slug] ?? null;
    }

    public function has(string $slug): bool
    {
        return $this->get($slug) !== null;
    }

    private function ensureBooted(): void
    {
        if ($this->booted) {
            return;
        }
        $this->booted = true;

        // Fresh install / mid-migration: no plugins table yet.
        try {
            if (! Schema::hasTable('plugins')) {
                return;
            }
        } catch (Throwable) {
            return;
        }

        $discovered = app(PluginManager::class)->discover();
        $enabled = app(CPanelPluginRepository::class)->enabledSlugs();

        foreach ($enabled as $slug) {
            $plugin = $discovered[$slug] ?? null;

            if ($plugin instanceof RegistersContentTypes) {
                foreach ($plugin->contentTypes() as $type) {
                    $this->register($type);
                }
            }
        }
    }
}

<?php

namespace Tests\Feature\Plugins;

use App\Repositories\CPanelPluginRepository;
use App\Support\PluginManager;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The reading-time plugin's `the_content` filter is wired into the post view and
 * only takes effect when the plugin is enabled.
 */
class ReadingTimeRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        app(PluginManager::class)->sync();
    }

    public function test_reading_time_absent_when_plugin_disabled(): void
    {
        $html = $this->get('/posts/introducing-the-cms')->assertOk()->getContent();
        $this->assertStringNotContainsString('min read', $html);
    }

    public function test_reading_time_shown_when_plugin_enabled(): void
    {
        app(CPanelPluginRepository::class)->setEnabled('reading-time', true);

        $html = $this->get('/posts/introducing-the-cms')->assertOk()->getContent();
        $this->assertStringContainsString('min read', $html);
    }
}

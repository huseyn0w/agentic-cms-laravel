<?php

namespace Tests\Feature\Front;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Category and tag archives share the React public/Archive page. Body is React;
 * the category route's CollectionPage + BreadcrumbList JSON-LD stays
 * server-rendered by Blade, so both are checked.
 */
class ArchiveInertiaRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.ensure_pages_exist' => false]);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_category_archive_renders_the_archive_component(): void
    {
        $this->get('/category/announcements')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('public/Archive')
                ->has('shell.menu')
                ->has('archive.title')
                ->has('archive.posts')
                ->has('archive.crumbs')
                ->where('archive.currentPage', 1)
        );
    }

    public function test_category_archive_ships_collectionpage_json_ld(): void
    {
        $html = $this->get('/category/announcements')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<title>'));
        $this->assertStringContainsString('"@type": "CollectionPage"', $html);
        $this->assertStringContainsString('"@type": "BreadcrumbList"', $html);
    }

    public function test_paginated_category_archive_renders(): void
    {
        $this->get('/category/announcements/page/1')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page->component('public/Archive')->where('archive.currentPage', 1)
        );
    }

    public function test_localized_category_archive_renders(): void
    {
        $this->get('/en/category/announcements')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page->component('public/Archive')
        );
    }
}

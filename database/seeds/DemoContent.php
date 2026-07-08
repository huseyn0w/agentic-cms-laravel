<?php

namespace Database\Seeders;

/**
 * Shared loader for the canonical trilingual (en/de/ru) demo dataset used by
 * the category / post / page seeders. The JSON lives at
 * database/seeds/data/demo-content-i18n.json (product name already substituted).
 *
 * Slugs are shared across locales (they are not translated); the *_translations
 * tables carry one row per locale keyed by the shared entity id.
 */
final class DemoContent
{
    /** Locales every demo item is seeded in (en is the required default). */
    public const LOCALES = ['en', 'de', 'ru'];

    /** Stable id assignment (dataset order): category slug => category id. */
    public const CATEGORY_IDS = [
        'announcements' => 1,
        'guides' => 2,
        'engineering' => 3,
    ];

    /** Stable id assignment (dataset order): tag slug => tag id. */
    public const TAG_IDS = [
        'cms' => 1,
        'seo' => 2,
        'themes' => 3,
        'plugins' => 4,
        'ai' => 5,
        'content' => 6,
    ];

    /** @var array<string,mixed>|null */
    private static ?array $data = null;

    /** @return array<string,mixed> */
    public static function all(): array
    {
        if (self::$data === null) {
            $path = __DIR__.'/data/demo-content-i18n.json';
            self::$data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        }

        return self::$data;
    }

    /** @return array<int,array<string,mixed>> */
    public static function categories(): array
    {
        return self::all()['categories'];
    }

    /** @return array<int,array<string,mixed>> */
    public static function tags(): array
    {
        return self::all()['tags'];
    }

    /** @return array<int,array<string,mixed>> Posts with a 1-based id in dataset order. */
    public static function posts(): array
    {
        $posts = [];
        $id = 1;
        foreach (self::all()['posts'] as $post) {
            $post['id'] = $id++;
            $posts[] = $post;
        }

        return $posts;
    }

    /**
     * Dataset pages keyed by slug (e.g. 'about', 'contact'). The page ids are
     * assigned by CPanelPagesSeeder (Homepage = 1, Contact = 2, About = 3).
     *
     * @return array<string,array<string,mixed>>
     */
    public static function pagesBySlug(): array
    {
        $bySlug = [];
        foreach (self::all()['pages'] as $page) {
            $bySlug[$page['slug']] = $page;
        }

        return $bySlug;
    }
}

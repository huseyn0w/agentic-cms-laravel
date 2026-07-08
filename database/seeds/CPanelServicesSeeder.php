<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CPanelServicesSeeder extends Seeder
{
    /**
     * Seed three sample services (published, en + ru). Direct table inserts
     * mirror CPanelPagesSeeder and bypass the Translatable trait/observers.
     *
     * @return void
     */
    public function run()
    {
        DB::table('services')->insertOrIgnore([
            ['id' => 1, 'sort_order' => 1],
            ['id' => 2, 'sort_order' => 2],
            ['id' => 3, 'sort_order' => 3],
        ]);

        $now = Carbon::now();

        $rows = [
            1 => [
                'icon' => '🌐',
                'en' => ['Web Development', 'web-development', 'Modern, fast websites built on Laravel.', '<p>We design and build modern, accessible, high-performance websites and web applications on Laravel.</p>'],
                'ru' => ['Веб-разработка', 'veb-razrabotka', 'Современные быстрые сайты на Laravel.', '<p>Проектируем и разрабатываем современные, доступные и производительные сайты и веб-приложения на Laravel.</p>'],
            ],
            2 => [
                'icon' => '🔍',
                'en' => ['SEO & GEO', 'seo-geo', 'Get found by search engines and AI.', '<p>Technical SEO plus GEO (generative engine optimization) so both search engines and AI assistants surface your business.</p>'],
                'ru' => ['SEO и GEO', 'seo-i-geo', 'Будьте заметны для поиска и ИИ.', '<p>Техническое SEO и GEO (оптимизация под генеративные движки), чтобы вас находили и поисковики, и ИИ-ассистенты.</p>'],
            ],
            3 => [
                'icon' => '🛠️',
                'en' => ['Support & Maintenance', 'support-maintenance', 'Ongoing care for your platform.', '<p>Proactive monitoring, updates and support to keep your platform secure, fast and up to date.</p>'],
                'ru' => ['Поддержка и сопровождение', 'podderzhka-soprovozhdenie', 'Постоянная забота о вашей платформе.', '<p>Проактивный мониторинг, обновления и поддержка, чтобы ваша платформа была безопасной, быстрой и актуальной.</p>'],
            ],
        ];

        $inserts = [];

        foreach ($rows as $id => $data) {
            foreach (['en', 'ru'] as $locale) {
                [$title, $slug, $excerpt, $content] = $data[$locale];

                $inserts[] = [
                    'service_id' => $id,
                    'locale' => $locale,
                    'title' => $title,
                    'slug' => $slug,
                    'icon' => $data['icon'],
                    'excerpt' => $excerpt,
                    'content' => $content,
                    'status' => 1,
                    'meta_description' => $excerpt,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('service_translations')->insertOrIgnore($inserts);
    }
}

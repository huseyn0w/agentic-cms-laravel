<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the compliance pages a German-facing site needs — Impressum and
 * Datenschutz — as normal published pages (template "page") in en/de/ru. The
 * body is an explicit placeholder: real legal text is site-specific and must be
 * filled in by the operator in the admin. Idempotent: a page is skipped if a
 * translation with its slug already exists, so re-running never duplicates.
 */
class LegalPagesSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $pages = [
            'impressum' => [
                'en' => ['title' => 'Imprint', 'body' => 'Imprint'],
                'de' => ['title' => 'Impressum', 'body' => 'Impressum'],
                'ru' => ['title' => 'Импрессум', 'body' => 'Импрессум'],
            ],
            'datenschutz' => [
                'en' => ['title' => 'Privacy Policy', 'body' => 'Privacy Policy'],
                'de' => ['title' => 'Datenschutz', 'body' => 'Datenschutzerklärung'],
                'ru' => ['title' => 'Политика конфиденциальности', 'body' => 'Политика конфиденциальности'],
            ],
        ];

        foreach ($pages as $slug => $locales) {
            if (DB::table('page_translations')->where('slug', $slug)->exists()) {
                continue;
            }

            DB::transaction(function () use ($slug, $locales, $now) {
                $pageId = DB::table('pages')->insertGetId(['deleted_at' => null]);

                foreach ($locales as $locale => $data) {
                    DB::table('page_translations')->insert([
                        'page_id' => $pageId,
                        'locale' => $locale,
                        'title' => $data['title'],
                        'slug' => $slug,
                        'author_id' => 1,
                        'status' => 1,
                        'template' => 'page',
                        'content' => '<h1>'.e($data['body']).'</h1>'
                            .'<p><em>Placeholder — replace this with your real legal text in the admin '
                            .'(Pages → '.e($data['title']).'). This page was scaffolded so the footer '
                            .'link resolves.</em></p>',
                        'meta_description' => '',
                        'meta_keywords' => '',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });
        }
    }
}

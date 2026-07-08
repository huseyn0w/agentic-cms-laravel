<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CPanelPagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $home_page_custom_fields = [
            'en' => [
                'headline' => [
                    'value' => 'Cmstack-Laravel',
                    'type' => 'text',
                    'admin_label' => 'Headline',
                ],
                'headline-image' => [
                    'value' => env('APP_URL').'/filemanager/images/5d9ca59b897a2.jpg',
                    'type' => 'image',
                    'admin_label' => 'Headline Image',
                ],
                'posts-from-category-headline' => [
                    'value' => 'Hot topics from Travel Section',
                    'type' => 'text',
                    'admin_label' => 'Posts from Category Headline',
                ],
                'posts-from-category-description' => [
                    'value' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                    'type' => 'text',
                    'admin_label' => 'Posts from Category description',
                ],
                'posts-from-category-cat-id' => [
                    'value' => '1',
                    'type' => 'category',
                    'admin_label' => 'Choose category',
                ],
                'about-headline' => [
                    'value' => 'About Us',
                    'type' => 'text',
                    'admin_label' => 'About Headline',
                ],
                'about-description' => [
                    'value' => 'Who exactly create this CMS?',
                    'type' => 'text',
                    'admin_label' => 'About description',
                ],
                'about-big-text' => [
                    'value' => '<p><strong>Elman Hüseynov</strong> - Full Stack Web Developer with more than 3 years of experience at freelance/office/remote jobs, completed more than 50 of projects and websites from scratch, currently Remote Full Stack Web Developer - located in Baku / Azerbaijan.</p>',
                    'type' => 'textarea',
                    'admin_label' => 'About Full Description',
                ],
                'authors' => [
                    'type' => 'repeater',
                    'admin_label' => 'Authors',
                    'value' => [
                        'row-0' => [
                            'author-image' => [
                                'value' => env('APP_URL').'/filemanager/images/5dbb536d16ce8.JPG',
                                'type' => 'image',
                                'admin_label' => 'Author Image',
                            ],
                            'author-name' => [
                                'value' => 'Elman Hüseynov',
                                'type' => 'text',
                                'admin_label' => 'Author Name',
                            ],
                            'author-position' => [
                                'value' => 'Cmstack-Laravel Author',
                                'type' => 'text',
                                'admin_label' => 'Author Position',
                            ],
                            'author-linkedin' => [
                                'value' => [
                                    'label' => '#',
                                    'url' => 'https://linkedin.com/in/huseyn0w/',
                                    'target' => '1',
                                ],
                                'type' => 'link',
                                'admin_label' => 'Author Linkedin',
                            ],
                        ],
                    ],
                ],

            ],
            'ru' => [
                'headline' => [
                    'value' => 'Cmstack-Laravel',
                    'type' => 'text',
                    'admin_label' => 'Заголовок',
                ],
                'headline-image' => [
                    'value' => env('APP_URL').'/filemanager/images/5d9ca59b897a2.jpg',
                    'type' => 'image',
                    'admin_label' => 'Заголовок изображения',
                ],
                'posts-from-category-headline' => [
                    'value' => 'Свежие новости с главной категории',
                    'type' => 'text',
                    'admin_label' => 'Заголовок секции постов с категории',
                ],
                'posts-from-category-description' => [
                    'value' => 'Описание будет тут',
                    'type' => 'text',
                    'admin_label' => 'Описание секции постов с категории',
                ],
                'posts-from-category-cat-id' => [
                    'value' => '1',
                    'type' => 'category',
                    'admin_label' => 'Выберите категорию',
                ],
                'about-headline' => [
                    'value' => 'О нас',
                    'type' => 'text',
                    'admin_label' => 'Заголовок раздела о нас',
                ],
                'about-description' => [
                    'value' => 'Немного об авторах',
                    'type' => 'text',
                    'admin_label' => 'Краткое описание раздела об авторах',
                ],
                'about-big-text' => [
                    'value' => '<p><strong>Эльман Гусейнов</strong> - Full Stack Web Разработчик с опытом работы более 3 лет в различных сферах начиная от фрилансера, заканчивая удаленной разработкой проектов, создал более 50 проектов с нуля, в данный момент является удаленным разработчиком - находится в Баку / Азербайджан.</p>',
                    'type' => 'textarea',
                    'admin_label' => 'Подробное описание раздела об авторах',
                ],
                'authors' => [
                    'type' => 'repeater',
                    'admin_label' => 'Authors',
                    'value' => [
                        'row-0' => [
                            'author-image' => [
                                'value' => env('APP_URL').'/filemanager/images/5dbb536d16ce8.JPG',
                                'type' => 'image',
                                'admin_label' => 'Изображение автора',
                            ],
                            'author-name' => [
                                'value' => 'Elman Hüseynov',
                                'type' => 'text',
                                'admin_label' => 'Имя автора',
                            ],
                            'author-position' => [
                                'value' => 'Создатель Cmstack-Laravel',
                                'type' => 'text',
                                'admin_label' => 'Должность',
                            ],
                            'author-linkedin' => [
                                'value' => [
                                    'label' => '#',
                                    'url' => 'https://linkedin.com/in/huseyn0w/',
                                    'target' => '1',
                                ],
                                'type' => 'link',
                                'admin_label' => 'Linkedin',
                            ],
                        ],
                    ],
                ],

            ],
        ];

        DB::table('pages')->insertOrIgnore([
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ]);

        // The German homepage reuses the English hero custom_fields (cosmetic)
        // with a localized title/meta, so the homepage exists in all three
        // locales like every other seeded page.
        $home_page_custom_fields['de'] = $home_page_custom_fields['en'];

        // Homepage (page 1) — one row per locale (en/de/ru).
        $home_meta = [
            'en' => ['title' => 'Homepage', 'keywords' => 'page, homepage', 'description' => 'This is homepage of CMS Cmstack-Laravel'],
            'de' => ['title' => 'Startseite', 'keywords' => 'seite, startseite', 'description' => 'Dies ist die Startseite des CMS Cmstack-Laravel'],
            'ru' => ['title' => 'Главная страница', 'keywords' => 'страница, главная', 'description' => 'Это главная страница CMS Cmstack-Laravel'],
        ];

        $homeRows = [];
        foreach (DemoContent::LOCALES as $locale) {
            $homeRows[] = [
                'title' => $home_meta[$locale]['title'],
                'locale' => $locale,
                'page_id' => 1,
                'slug' => '/',
                'author_id' => 1,
                'status' => 1,
                'meta_keywords' => $home_meta[$locale]['keywords'],
                'meta_description' => $home_meta[$locale]['description'],
                'custom_fields' => json_encode($home_page_custom_fields[$locale]),
                'template' => 'home',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }
        DB::table('page_translations')->insertOrIgnore($homeRows);

        // Content pages from the canonical dataset: Contact (page 2) and About
        // (page 3), one row per locale, slug shared across locales. Inserted in
        // their own batch because they carry the `content` column the Homepage
        // rows omit (a single batch insert needs a uniform column set).
        $pages = DemoContent::pagesBySlug();
        $pageMap = [
            2 => ['data' => $pages['contact'], 'template' => 'contacts'],
            3 => ['data' => $pages['about'], 'template' => 'page'],
        ];

        $contentRows = [];
        foreach ($pageMap as $pageId => $meta) {
            foreach (DemoContent::LOCALES as $locale) {
                $contentRows[] = [
                    'title' => $meta['data']['title'][$locale],
                    'slug' => $meta['data']['slug'],
                    'locale' => $locale,
                    'page_id' => $pageId,
                    'author_id' => 1,
                    'status' => 1,
                    'meta_keywords' => $meta['data']['slug'].', cmstack, cms',
                    'meta_description' => mb_substr(strip_tags($meta['data']['content'][$locale]), 0, 200),
                    'template' => $meta['template'],
                    'content' => $meta['data']['content'][$locale],
                    'custom_fields' => json_encode([]),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
        }
        DB::table('page_translations')->insertOrIgnore($contentRows);
    }
}

<?php

/**
 * Cmstack-Laravel
 * File: PageRepository.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 24.10.2019
 */

namespace App\Repositories;

use App\Http\Models\Category;
use App\Http\Models\CategoryTranslation;
use App\Http\Models\Post;
use Doctrine\DBAL\Driver\PDOException;
use Illuminate\Database\QueryException;

class CategoryRepository extends BaseRepository
{
    protected $main_table = 'categories';

    protected $translated_table = 'category_translations';

    protected $translated_table_join_column = 'category_id';

    protected $select_fields = [
        'id',
        'author_id',
        'title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'meta_noindex',
        'description',
        'slug',
    ];

    public function __construct(Category $model)
    {
        parent::__construct();
        $this->model = $model;

        $this->translated_table_model = new CategoryTranslation;

    }

    /**
     * Sitemap rows for all categories: one row per (category, locale)
     * translation (category_translations has no timestamps) used to build
     * <url>/<xhtml:link> entries.
     */
    public function sitemapEntries()
    {
        return Category::join('category_translations', 'categories.id', '=', 'category_translations.category_id')
            ->select('categories.id', 'category_translations.slug', 'category_translations.locale', 'category_translations.category_id')
            ->get();
    }

    /**
     * Up to 20 categories (in the default app locale) for the llms.txt
     * "Categories" link list — title + slug only.
     */
    public function llmsEntries()
    {
        return Category::join('category_translations', 'categories.id', '=', 'category_translations.category_id')
            ->where('category_translations.locale', config('app.locale'))
            ->select('category_translations.title', 'category_translations.slug')
            ->limit(20)->get();
    }

    /**
     * Resolve a category by its slug in the given locale for the per-category
     * syndication feeds (FEATURE_MATRIX §16). Returns the id/title/slug row, or
     * null when no category matches. Locale-scoped so the feed names/matches the
     * category in the feed's language.
     */
    public function findForFeedBySlug(string $slug, string $locale)
    {
        return CategoryTranslation::where('locale', $locale)
            ->where('slug', $slug)
            ->select('category_id', 'title', 'slug')
            ->first();
    }

    public function displayList(int $category_id, int $page = 1)
    {
        $this->locale = get_current_lang();

        $main_table_name = 'posts';
        $translated_table_name = 'post_translations';
        $join_column = 'post_id';

        $select_fields = [
            'id',
            'title',
            'slug',
            'thumbnail',
            'preview',
            'likes',
            'created_at',
        ];

        $select = $this->generateSelectFieldsArray($select_fields, $main_table_name, $translated_table_name);

        $count = get_general_settings('posts_per_page');

        try {
            $data = Post::join($translated_table_name, $main_table_name.'.id', '=', $translated_table_name.'.'.$join_column)
                ->select($select)
                ->where($translated_table_name.'.locale', $this->locale)
                ->notScheduledForFuture()
                ->whereHas('categories', function ($query) use ($category_id) {
                    $query->select('category_id');
                    $query->where('category_id', $category_id);
                })
                ->with('author')->paginate($count, ['*'], 'page', $page);

        } catch (QueryException $e) {
            //            dd($e->getMessage());
            throwAbort();
        } catch (PDOException $e) {
            //            dd($e->getMessage());
            throwAbort();
        } catch (\Error $e) {
            //            dd($e->getMessage());
            throwAbort();
        }

        return $data;
    }
}

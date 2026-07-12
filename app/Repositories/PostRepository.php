<?php

/**
 * AgenticCms-Laravel
 * File: PageRepository.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 24.10.2019
 */

namespace App\Repositories;

use App\Http\Models\Likes;
use App\Http\Models\Post;
use App\Http\Models\PostTranslation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PostRepository extends BaseRepository
{
    protected $main_table = 'posts';

    protected $translated_table = 'post_translations';

    protected $translated_table_join_column = 'post_id';

    protected $select_fields = [
        'id',
        'author_id',
        'title',
        'content',
        'likes',
        'thumbnail',
        'slug',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'meta_noindex',
        'status',
        'created_at',
        'updated_at',
    ];

    public function __construct(Post $model)
    {
        parent::__construct();
        $this->model = $model;
        $this->translated_table_model = new PostTranslation;
    }

    /**
     * Sitemap rows for all posts: one row per (post, locale) translation with
     * the slug + updated_at used to build <url>/<lastmod>/<xhtml:link> entries.
     */
    public function sitemapEntries()
    {
        return Post::join('post_translations', 'posts.id', '=', 'post_translations.post_id')
            ->select('posts.id', 'post_translations.slug', 'post_translations.locale', 'post_translations.updated_at', 'post_translations.post_id')
            ->notScheduledForFuture()
            ->get();
    }

    /**
     * Feed rows for the public syndication feeds (RSS/Atom): the most recent
     * PUBLISHED posts in the given locale, newest first. Drafts (status != 1) and
     * future-scheduled posts are excluded so unpublished content never leaks.
     *
     * @return Collection
     */
    public function feedEntries(string $locale, int $limit = 20)
    {
        return Post::join('post_translations', 'posts.id', '=', 'post_translations.post_id')
            ->select(
                'posts.id',
                'post_translations.title',
                'post_translations.slug',
                'post_translations.preview',
                'post_translations.content',
                'post_translations.created_at',
                'post_translations.updated_at',
            )
            ->where('post_translations.locale', $locale)
            ->where('post_translations.status', Post::STATUS_PUBLISHED)
            ->notScheduledForFuture()
            ->orderByDesc('post_translations.created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Feed rows for a single category's syndication feed (FEATURE_MATRIX §16):
     * the most recent PUBLISHED posts in the given locale that belong to the
     * category, newest first. Same published-only / no-future-scheduled
     * guarantees as feedEntries().
     *
     * @return Collection
     */
    public function feedEntriesForCategory(int $categoryId, string $locale, int $limit = 20)
    {
        return Post::join('post_translations', 'posts.id', '=', 'post_translations.post_id')
            ->select(
                'posts.id',
                'post_translations.title',
                'post_translations.slug',
                'post_translations.preview',
                'post_translations.content',
                'post_translations.created_at',
                'post_translations.updated_at',
            )
            ->where('post_translations.locale', $locale)
            ->where('post_translations.status', Post::STATUS_PUBLISHED)
            ->notScheduledForFuture()
            ->whereHas('categories', function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->orderByDesc('post_translations.created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Restrict public single-post reads to PUBLISHED posts only, while also
     * hiding future-scheduled drafts (notScheduledForFuture). The two
     * constraints together mean:
     *   - status must be PUBLISHED (1) — plain drafts are always hidden.
     *   - a published post with a lingering future schedule IS still visible
     *     (the notScheduledForFuture scope's OR-status=1 branch allows it).
     *
     * Column is post_translations.status (status lives on the translations table).
     *
     * @param  mixed  $query
     * @return mixed
     */
    protected function applyFrontReadScope($query)
    {
        return $query
            ->where('post_translations.status', '=', Post::STATUS_PUBLISHED)
            ->notScheduledForFuture();
    }

    /**
     * Related posts for the given post (FEATURE_MATRIX §1): other PUBLISHED
     * posts that share at least one category or tag with it, ranked by the
     * number of shared terms (most-related first, newest as a tiebreak) and
     * capped at $limit.
     *
     * Scoping rules (mirrors the ts canonical): the source post is excluded;
     * drafts and future-scheduled posts never appear; results are scoped to the
     * given locale so titles/slugs resolve in the reader's language.
     *
     * Returns an empty collection when the source post carries no taxonomy.
     *
     * @return Collection<int, PostTranslation>
     */
    public function getRelated(int $post_id, string $locale, int $limit = 4): Collection
    {
        $categoryIds = DB::table('category_post')->where('post_id', $post_id)->pluck('category_id')->all();
        $tagIds = DB::table('post_tag')->where('post_id', $post_id)->pluck('tag_id')->all();

        if (empty($categoryIds) && empty($tagIds)) {
            return new Collection;
        }

        // Candidate ids that share ANY category or tag with the source post
        // (OR-overlap), together with the count of shared terms for ranking.
        $union = [];

        if (! empty($categoryIds)) {
            $union[] = DB::table('category_post')
                ->select('post_id')
                ->whereIn('category_id', $categoryIds);
        }
        if (! empty($tagIds)) {
            $union[] = DB::table('post_tag')
                ->select('post_id')
                ->whereIn('tag_id', $tagIds);
        }

        $matches = array_shift($union);
        foreach ($union as $next) {
            $matches->unionAll($next);
        }

        // Aggregate shared-term counts per candidate post (excluding the source).
        $scored = DB::query()
            ->fromSub($matches, 'm')
            ->select('m.post_id', DB::raw('COUNT(*) as shared'))
            ->where('m.post_id', '!=', $post_id)
            ->groupBy('m.post_id')
            ->pluck('shared', 'post_id')
            ->all();

        if (empty($scored)) {
            return new Collection;
        }

        // Fetch the published, locale-scoped translations for those candidates,
        // hiding drafts and future-scheduled posts. Fetch a generous slice, then
        // rank in PHP by shared-term count (recency tiebreak) and cap.
        $rows = PostTranslation::query()
            ->join('posts', 'posts.id', '=', 'post_translations.post_id')
            ->whereNull('posts.deleted_at')
            ->whereIn('post_translations.post_id', array_keys($scored))
            ->where('post_translations.locale', $locale)
            ->where('post_translations.status', Post::STATUS_PUBLISHED)
            ->where(function ($q) {
                $q->whereNull('post_translations.scheduled_at')
                    ->orWhere('post_translations.scheduled_at', '<=', now());
            })
            ->select('post_translations.*')
            ->orderByDesc('post_translations.created_at')
            ->limit(max($limit, 1) * 4)
            ->get();

        return $rows
            ->sortByDesc(fn ($row) => $scored[$row->post_id] ?? 0)
            ->take(max($limit, 1))
            ->values();
    }

    public function handleLike(int $post_id, int $user_id)
    {
        if (Auth::user()->id !== $user_id) {
            return false;
        }

        $result = false;

        $data = Likes::where('post_id', $post_id)->where('user_id', $user_id)->first();
        if (empty($data)) {
            $like_added = Likes::insert([
                ['user_id' => $user_id, 'post_id' => $post_id],
            ]);

            if ($like_added) {
                PostTranslation::where('post_id', $post_id)->increment('likes');
                $result = trans('default/post.like_added');
            }
        } else {
            $like_deleted = Likes::where('post_id', $post_id)->where('user_id', $user_id)->delete();
            if ($like_deleted) {
                PostTranslation::where('post_id', $post_id)->decrement('likes');
                $result = trans('default/post.like_deleted');
            }
        }

        return $result;
    }

    public function getTranslatedBy($param, $value)
    {
        $comments_per_page = get_comments_count_per_page();
        $data = parent::getTranslatedBy($param, $value);

        // A non-existent slug yields null here; let the caller surface the 404
        // (BaseController::index throwNotFound) instead of fatally calling
        // setRelation() on null.
        if (is_null($data)) {
            return null;
        }

        $data->setRelation('comments', $data->comments()->with('replies')->with('user')->orderBy('id', 'DESC')->paginate($comments_per_page));

        return $data;
    }
}

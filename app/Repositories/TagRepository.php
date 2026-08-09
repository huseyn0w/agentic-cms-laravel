<?php

namespace App\Repositories;

use App\Http\Models\Post;
use App\Http\Models\Tag;
use App\Http\Models\TagTranslation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

/**
 * Persistence for the translatable Tag taxonomy: find-or-create tags by name
 * (deriving a slug) and sync them to a post. All ORM access for tags lives here
 * so services/observers only delegate.
 */
class TagRepository extends BaseRepository
{
    protected $main_table = 'tags';

    protected $translated_table = 'tag_translations';

    protected $translated_table_join_column = 'tag_id';

    // Tags have no author relation, unlike content models.
    protected $eager_relations = [];

    protected $select_fields = [
        'id',
        'name',
        'slug',
    ];

    public function __construct(Tag $model)
    {
        parent::__construct();
        $this->model = $model;
        $this->translated_model = new TagTranslation;
    }

    /**
     * Find-or-create each named tag (current locale) and sync the post's tags
     * to exactly that set. Blank names are ignored. Passing an empty list
     * detaches all tags.
     *
     * @param  array<int, string>  $names
     */
    public function syncToPost(Post $post, array $names): void
    {
        $ids = [];

        foreach ($names as $name) {
            $name = trim((string) $name);

            if ($name === '') {
                continue;
            }

            $ids[] = $this->findOrCreateByName($name)->id;
        }

        $post->tags()->sync(array_values(array_unique($ids)));
    }

    /**
     * Resolve a tag by its slug (or name) in the active locale, creating it
     * when absent.
     */
    public function findOrCreateByName(string $name): Tag
    {
        // Use the same locale source as the read paths (postsForTag /
        // getTranslatedBy) so a tag is written and resolved under one locale.
        $locale = get_current_lang();
        $slug = Str::slug($name);

        $existing = $this->model->whereTranslation('slug', $slug, $locale)->first()
            ?? $this->model->whereTranslation('name', $name, $locale)->first();

        if ($existing) {
            return $existing;
        }

        return $this->model->create(['name' => $name, 'slug' => $slug]);
    }

    /**
     * Paginated list of posts carrying the given tag, for the public archive
     * (locale-scoped, mirrors CategoryRepository::displayList).
     */
    public function postsForTag(int $tag_id, int $page = 1)
    {
        $locale = get_current_lang();

        $main_table_name = 'posts';
        $translated_table_name = 'post_translations';
        $join_column = 'post_id';

        // updated_at is needed by PublicArchive; selecting it here avoids an
        // astrotomic per-post translation lazy-load (N+1) on the listing.
        $select = $this->generateSelectFieldsArray(
            ['id', 'title', 'slug', 'thumbnail', 'preview', 'likes', 'created_at', 'updated_at'],
            $main_table_name,
            $translated_table_name
        );

        $count = get_general_settings('posts_per_page');

        try {
            return Post::join($translated_table_name, $main_table_name.'.id', '=', $translated_table_name.'.'.$join_column)
                ->select($select)
                ->where($translated_table_name.'.locale', $locale)
                ->notScheduledForFuture()
                ->whereHas('tags', function ($query) use ($tag_id) {
                    $query->where('tags.id', $tag_id);
                })
                ->with('author')->paginate($count, ['*'], 'page', $page);
        } catch (QueryException $e) {
            throwAbort();
        } catch (\Error $e) {
            throwAbort();
        }
    }
}

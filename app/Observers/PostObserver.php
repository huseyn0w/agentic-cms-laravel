<?php

namespace App\Observers;

use App\Http\Models\Category;
use App\Http\Models\Post;
use App\Repositories\TagRepository;

class PostObserver extends AgenticCmsLaravelObserver
{
    /**
     * Handle the post "saving" event.
     *
     * @return void
     */
    public function saving(Post $post)
    {
        if (! is_null($post->post_id)) {
            $this->detachCategory($post);
            $this->attachCategory($post);
            $this->syncTags($post);
        }
    }

    /**
     * Handle the post "created" event.
     *
     * @return void
     */
    public function created(Post $post)
    {
        $this->attachCategory($post);
        $this->syncTags($post);
    }

    /**
     * Handle the post "force deleted" event.
     *
     * @param  Post  $post
     * @return void
     */
    public function forceDeleted($post)
    {
        $this->detachCategory($post);
        $post->tags()->detach();
    }

    private function attachCategory($post)
    {
        $categories_list = $this->request->category;
        //        dd($categories_list);
        $category = Category::find($categories_list);
        $post->categories()->attach($category);
    }

    private function detachCategory($post)
    {
        $post->categories()->detach();
    }

    /**
     * Sync the post's tags from the submitted `tags` input (array of names or a
     * comma-separated string). Only acts when the form actually submitted a
     * `tags` field, so unrelated saves never wipe existing tags. Find-or-create
     * + sync lives in TagRepository (the observer only delegates).
     */
    private function syncTags($post)
    {
        if (! $this->request->has('tags')) {
            return;
        }

        $names = $this->request->input('tags');

        if (is_string($names)) {
            $names = $names === '' ? [] : explode(',', $names);
        }

        app(TagRepository::class)->syncToPost($post, is_array($names) ? $names : []);
    }
}

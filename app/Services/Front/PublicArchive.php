<?php

namespace App\Services\Front;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Shapes a post-listing archive (category or tag) for the shared React
 * public/Archive page: the title, breadcrumb, the current page of post cards,
 * and the pagination cursor. Category and tag archives are the same listing;
 * only the source model differs, so both call this.
 */
class PublicArchive
{
    /**
     * @param  LengthAwarePaginator<int, object>  $posts
     * @return array<string, mixed>
     */
    public function build(string $title, LengthAwarePaginator $posts, string $pageBaseUrl, string $emptyText): array
    {
        $base = rtrim(config('app.url'), '/');
        $localePrefix = get_current_lang() === default_lang() ? '' : get_current_lang().'/';
        $home = get_general_settings('website_name') ?: config('app.name');

        return [
            'title' => $title,
            'crumbs' => [
                ['label' => $home, 'url' => $base],
                ['label' => $title, 'url' => null],
            ],
            'posts' => collect($posts->items())->map(fn ($post) => [
                'title' => $post->title,
                'url' => $base.'/'.$localePrefix.'posts/'.$post->slug,
                'excerpt' => strip_tags((string) $post->preview),
                'image' => image_src($post->thumbnail),
                'date' => Carbon::parse($post->updated_at)->format('Y-m-d'),
            ])->values()->all(),
            'currentPage' => $posts->currentPage(),
            'lastPage' => $posts->lastPage(),
            'total' => $posts->total(),
            // Page N link = "{pageBaseUrl}/page/{N}"; the display_pages route
            // accepts page 1 too, so every link uses the same shape.
            'pageBaseUrl' => $base.'/'.$localePrefix.$pageBaseUrl,
            'emptyText' => $emptyText,
        ];
    }
}

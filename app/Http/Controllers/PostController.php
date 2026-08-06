<?php

namespace App\Http\Controllers;

use App\Http\Requests\LikesRequest;
use App\Services\Front\PostViewService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PostController extends BaseController
{
    public function __construct(PostViewService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function index(string $post_slug, ?string $locale = null): Response|HttpResponse
    {
        $result = parent::index($post_slug, $locale);

        if (is_object($result)) {
            return $result;
        }

        $post = $this->data;
        $localePrefix = get_current_lang() === config('app.locale') ? '' : get_current_lang().'/';
        $base = rtrim(config('app.url'), '/');

        return Inertia::render('public/Post', [
            'currentUserId' => Auth::id(),
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'content' => app('hooks')->filter('the_content', $post->content),
                'thumbnail' => $post->thumbnail ?: null,
                'date' => Carbon::parse($post->updated_at)->format('d.m.Y'),
                'dateIso' => Carbon::parse($post->updated_at)->toIso8601String(),
                'likes' => (int) $post->likes,
                'liked' => check_if_post_liked_by_current_user($post->id),
                'likeUrl' => route('handle_post_likes', ['id' => $post->id]),
                'author' => [
                    'name' => trim(($post->author->name ?? '').' '.($post->author->surname ?? '')),
                    'username' => $post->author->username,
                    'url' => route('show_user', ['username' => $post->author->username]),
                    'avatar' => image_src($post->author->avatar ?? null, true),
                ],
                'category' => isset($post->categories[0]) ? [
                    'title' => $post->categories[0]->title,
                    'url' => $base.'/'.$localePrefix.'category/'.$post->categories[0]->slug,
                ] : null,
                'tags' => collect($post->tags ?? [])->map(fn ($tag) => [
                    'name' => $tag->name,
                    'url' => $base.'/'.$localePrefix.'tag/'.$tag->slug,
                ])->values()->all(),
            ],
            'related' => collect($this->service->related($post->id))->map(fn ($r) => [
                'title' => $r->title,
                'url' => $base.'/'.$localePrefix.'posts/'.$r->slug,
                'excerpt' => strip_tags((string) $r->preview),
                'date' => Carbon::parse($r->updated_at)->format('Y-m-d'),
                'image' => image_src($r->thumbnail),
            ])->values()->all(),
            'comments' => $this->shapeComments(),
            'commentForm' => [
                'postUrl' => route('store_post_comments', ['id' => $post->id]),
                'editUrl' => route('update_post_comment'),
                'deleteBase' => url('/posts/deletecomment'),
                'canComment' => is_logged_in(),
                'canManageComments' => Auth::user()?->can('manage_comments', 'App\Http\Models\UserRoles') ?? false,
                'loginUrl' => route('login'),
            ],
        ])
            ->rootView('app-public')
            ->withViewData([
                'data' => $post,
                'author' => trim(($post->author->name ?? '').' '.($post->author->surname ?? '')) ?: null,
            ]);
    }

    /**
     * The paginated top-level comments with one level of replies, shaped for the
     * React thread. Owner/admin edit + delete are a follow-up; this cut renders
     * the thread and the create form.
     *
     * @return array<string, mixed>
     */
    private function shapeComments(): array
    {
        $paginator = $this->data->comments;

        $map = fn ($comment): array => [
            'id' => $comment->id,
            'body' => $comment->comment,
            'date' => Carbon::parse($comment->created_at)->format('d.m.Y'),
            'user' => [
                'id' => $comment->user->id,
                'name' => $comment->user->name,
                'username' => $comment->user->username,
                'url' => route('show_user', ['username' => $comment->user->username]),
                'avatar' => image_src($comment->user->avatar ?? null, true),
            ],
        ];

        $items = collect($paginator->items())->map(function ($comment) use ($map) {
            $row = $map($comment);
            $row['replies'] = collect($comment->replies ?? [])->map($map)->values()->all();

            return $row;
        })->values()->all();

        return [
            'total' => $paginator->total(),
            'data' => $items,
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'currentUserId' => Auth::id(),
        ];
    }

    public function handleLike(LikesRequest $request)
    {
        $result = $this->service->like($request['postId'], $request['userId']);

        return json_encode($result);
    }
}

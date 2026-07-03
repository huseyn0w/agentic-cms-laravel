<?php

namespace App\Http\Controllers;

use App\Http\Requests\LikesRequest;
use App\Services\Front\PostViewService;

class PostController extends BaseController
{
    public function __construct(PostViewService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function index(string $post_slug, ?string $locale = null)
    {
        $result = parent::index($post_slug, $locale);

        if (is_object($result)) {
            return $result;
        }

        $related = $this->service->related($this->data->id);

        return view('default/posts/post', ['data' => $this->data, 'related' => $related]);
    }

    public function handleLike(LikesRequest $request)
    {
        $result = $this->service->like($request['postId'], $request['userId']);

        return json_encode($result);
    }
}

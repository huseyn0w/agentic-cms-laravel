<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostCommentsRequest;
use App\Services\Front\CommentService;

class PostCommentController extends BaseController
{
    public function __construct(CommentService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function store(PostCommentsRequest $request)
    {
        if ($this->service->create($request)) {
            return back()->with('comment_added', true);
        }

        return false;
    }

    public function delete(PostCommentsRequest $request)
    {
        $this->service->delete($request);

        return back()->with('comment_deleted', true);
    }

    public function update(PostCommentsRequest $request)
    {
        $result = $this->service->update($request);

        if ($result) {
            return back()->with('comment_updated', true);
        }

        return false;
    }
}

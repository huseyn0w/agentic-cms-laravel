<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Requests\CPanelCommentsRequest;
use App\Services\CPanel\CPanelCommentService;
use Inertia\Inertia;

class CPanelCommentController extends CPanelBaseController
{
    public function __construct(CPanelCommentService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function index()
    {
        $comments_list = $this->service->list($this->per_page);

        $comments_list->getCollection()->transform(fn ($c) => [
            'id' => $c->id,
            'post_title' => $c->post?->title,
            'comment' => $c->comment,
            'author' => $c->user?->username,
            'date' => $c->created_at?->format('d.m.Y'),
            'status' => (int) $c->status,
        ]);

        return Inertia::render('cpanel/comments/List', [
            'comments_list' => $comments_list,
        ]);
    }

    public function approve(int $id)
    {
        $this->validateCommentID($id);

        $this->service->approve($id);

        return back()->with('success', __('cpanel/comments.js_approve'));
    }

    public function unApprove(int $id)
    {
        $this->validateCommentID($id);

        $this->service->unApprove($id);

        return back()->with('success', __('cpanel/comments.js_unapprove'));
    }

    public function multipleDelete(CPanelCommentsRequest $request)
    {
        $this->service->delete($request->comments);

        return back()->with('success', __('cpanel/comments.js_delete'));
    }

    public function validateCommentID($id)
    {
        if ($id <= 0) {
            echo trans('cpanel/controller.id_int');

            return false;
        }

        return true;
    }
}

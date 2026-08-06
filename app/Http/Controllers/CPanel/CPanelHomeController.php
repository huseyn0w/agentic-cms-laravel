<?php

namespace App\Http\Controllers\CPanel;

use App\Services\CPanel\CPanelDashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CPanelHomeController extends CPanelBaseController
{
    private CPanelDashboardService $dashboard;

    public function __construct(CPanelDashboardService $dashboard)
    {
        parent::__construct();
        $this->dashboard = $dashboard;
    }

    public function index(Request $request)
    {
        $count = 5;
        $posts = $this->dashboard->latestPosts($count);
        $users = $this->dashboard->latestUsers($count);
        $comments = $this->dashboard->latestComments($count);

        return Inertia::render('cpanel/Dashboard', [
            'posts' => $posts,
            'users' => $users,
            'comments' => $comments,
            'counts' => $this->dashboard->counts(),
        ]);
    }
}

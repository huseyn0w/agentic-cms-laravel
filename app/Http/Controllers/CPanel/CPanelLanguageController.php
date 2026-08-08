<?php

namespace App\Http\Controllers\CPanel;

class CPanelLanguageController extends CPanelBaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index($lang)
    {
        if (lang_exist($lang)) {
            \Session::put('locale', $lang);
        }

        // Stay on the page the switch was triggered from; fall back to the
        // dashboard when there is no referer.
        return redirect()->back(302, [], route('cpanel_home'));
    }
}

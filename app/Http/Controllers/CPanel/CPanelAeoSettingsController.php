<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Requests\ValidateAeoSettings;
use App\Services\CPanel\SeoSettingsService;
use Inertia\Inertia;

/**
 * Admin AEO settings page: allow or block AI / answer-engine crawlers
 * (GPTBot, ClaudeBot, PerplexityBot, …). The toggles live on the SEO settings
 * singleton's ai_crawlers field and feed the robots.txt builder — a bot is
 * allowed by default and only gets a Disallow stanza when turned off.
 * Gated by manage_general_settings on the route group.
 */
class CPanelAeoSettingsController extends CPanelBaseController
{
    public function __construct(SeoSettingsService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function index()
    {
        $seo = $this->service->currentOrNew();
        $stored = is_array($seo->ai_crawlers) ? $seo->ai_crawlers : [];

        $crawlers = [];
        $catalog = [];
        foreach (config('ai_crawlers', []) as $key => $bot) {
            $crawlers[$key] = ($stored[$key] ?? true) !== false;
            $catalog[] = ['key' => $key, 'label' => $bot['label']];
        }

        return Inertia::render('cpanel/settings/Aeo', [
            'ai_crawler_catalog' => $catalog,
            'ai_crawlers' => $crawlers,
        ]);
    }

    public function store(ValidateAeoSettings $request)
    {
        $this->service->saveAiCrawlers($request->validated()['ai_crawlers']);

        return back()->with('success', __('cpanel/settings.aeo_settings_updates_success'));
    }
}

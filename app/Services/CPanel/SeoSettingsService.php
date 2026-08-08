<?php

namespace App\Services\CPanel;

use App\Repositories\CPanelSeoSettingsRepository;
use App\Services\BaseCrudService;

/**
 * Domain service for the global SEO settings singleton (row id = 1).
 * Owns all data access; the controller only maps results to HTTP responses.
 */
class SeoSettingsService extends BaseCrudService
{
    public function __construct(private CPanelSeoSettingsRepository $repo)
    {
        parent::__construct($repo);
    }

    /**
     * Always return a model instance even on a fresh DB so the settings form
     * can bind to it (singleton row id = 1).
     */
    public function currentOrNew()
    {
        return $this->repo->firstOrNew();
    }

    /**
     * Persist the SEO settings singleton from validated input. All persistence
     * lives in the repository — the service never touches the model directly.
     */
    public function save($request)
    {
        return $this->repo->saveSingleton($request);
    }

    /**
     * Persist only the AEO / AI-crawler allow map (owned by the AEO settings tab).
     *
     * @param  array<string, bool>  $crawlers
     */
    public function saveAiCrawlers(array $crawlers)
    {
        return $this->repo->saveAiCrawlers($crawlers);
    }
}

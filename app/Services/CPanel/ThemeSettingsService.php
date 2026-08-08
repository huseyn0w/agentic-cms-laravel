<?php

namespace App\Services\CPanel;

use App\Repositories\CPanelThemeSettingsRepository;
use App\Services\BaseCrudService;

/**
 * Domain service for the global theme settings singleton (row id = 1).
 * Owns all data access; the controller only maps results to HTTP responses.
 */
class ThemeSettingsService extends BaseCrudService
{
    public function __construct(private CPanelThemeSettingsRepository $repo)
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
     * Persist the theme settings singleton from validated input. All persistence
     * lives in the repository — the service never touches the model directly.
     */
    public function save($request)
    {
        return $this->repo->saveSingleton($request);
    }
}

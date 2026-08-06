<?php

namespace App\Services\CPanel;

use App\Repositories\CPanelSecuritySettingsRepository;
use App\Services\BaseCrudService;

/**
 * Domain service for the global security settings singleton (row id = 1).
 * Owns all data access; the controller only maps results to HTTP responses.
 */
class SecuritySettingsService extends BaseCrudService
{
    public function __construct(private CPanelSecuritySettingsRepository $repo)
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
     * Persist the security settings singleton from validated input. All
     * persistence lives in the repository — the service never touches the model.
     */
    public function save($request)
    {
        return $this->repo->saveSingleton($request);
    }
}

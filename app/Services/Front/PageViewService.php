<?php

namespace App\Services\Front;

use App\Repositories\PageRepository;
use App\Services\BaseCrudService;

/**
 * Front-end page view service: resolves the public page (via the inherited
 * resolveBySlug) for slug-routed rendering. All data access goes through
 * PageRepository — the controller never touches it directly.
 */
class PageViewService extends BaseCrudService
{
    public function __construct(private PageRepository $repo)
    {
        parent::__construct($repo);
    }

    /**
     * Resolve a page by id for the admin-only preview screen (drafts included).
     * Null when it has no translation in the current locale. See
     * PageRepository::resolveByIdForPreview.
     */
    public function previewById(int $id)
    {
        return $this->repo->resolveByIdForPreview($id);
    }
}

<?php

namespace App\Observers;

use App\Http\Models\PageTranslation;
use App\Repositories\RevisionRepository;

class PageTranslationObserver extends AgenticCmsLaravelObserver
{
    /**
     * Snapshot the pre-edit page translation before an update is persisted.
     * Side effect of a write — delegated to the repository (no inline ORM).
     *
     * @return void
     */
    public function updating(PageTranslation $pageTranslation)
    {
        app(RevisionRepository::class)->snapshot($pageTranslation);
    }
}

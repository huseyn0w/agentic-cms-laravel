<?php

namespace App\Observers;

use App\Http\Models\PostTranslation;
use App\Repositories\RevisionRepository;

class PostTranslationObserver extends AgenticCmsLaravelObserver
{
    /**
     * Handle the post translation "saving" event.
     *
     * @return void
     */
    public function saving(PostTranslation $postTranslation)
    {
        // Only sanitise from the request when those fields were actually
        // submitted (the edit form path). Writes that don't carry them — e.g. a
        // revision restore — must keep the values already set on the model
        // rather than have them clobbered to empty.
        if ($this->request->has('preview')) {
            $postTranslation->preview = clean($this->request->preview);
        }

        if ($this->request->has('content')) {
            $postTranslation->content = clean($this->request->content);
        }
    }

    /**
     * Snapshot the pre-edit translation before an update is persisted. Side
     * effect of a write — delegated to the repository (no inline ORM here).
     *
     * @return void
     */
    public function updating(PostTranslation $postTranslation)
    {
        app(RevisionRepository::class)->snapshot($postTranslation);
    }
}

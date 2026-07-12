<?php

namespace App\Observers;

use App\Http\Models\ServiceTranslation;

class ServiceTranslationObserver extends AgenticCmsLaravelObserver
{
    /**
     * Sanitize rich text before persisting. Services have no revision history,
     * so (unlike PostTranslationObserver) this cleans the model attributes
     * directly rather than re-reading the request — robust for both the admin
     * form path and programmatic (MCP) writes.
     */
    public function saving(ServiceTranslation $translation): void
    {
        if (! empty($translation->content)) {
            $translation->content = clean($translation->content);
        }

        if (! empty($translation->excerpt)) {
            $translation->excerpt = clean($translation->excerpt);
        }
    }
}

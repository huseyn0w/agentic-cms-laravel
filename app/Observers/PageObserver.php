<?php

namespace App\Observers;

use App\Http\Models\Page;

class PageObserver extends AgenticCmsLaravelObserver
{
    /**
     * Handle the page "creating" event.
     *
     * @return void
     */
    public function creating(Page $page)
    {
        $this->setCustomFields($page, $this->request->custom_fields);
        $this->sanitize_content($page, $this->request->content);
    }

    /**
     * Handle the page "updating" event.
     *
     * @return void
     */
    public function updating(Page $page)
    {
        $this->setCustomFields($page, $this->request->custom_fields);
        $this->sanitize_content($page, $this->request->content);
    }

    private function sanitize_content($model, $content)
    {
        if (isset($content)) {
            $model->content = clean($content);
        }
    }

    private function setCustomFields($model, $custom_fields)
    {
        if (isset($custom_fields)) {
            $model->custom_fields = json_encode($custom_fields);
        }
    }
}

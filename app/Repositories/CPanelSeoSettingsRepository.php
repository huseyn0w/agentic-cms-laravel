<?php

/**
 * AgenticCms-Laravel
 * File: CPanelSeoSettingsRepository.php
 * Phase 7 (SEO/GEO): persistence for the global SEO settings singleton.
 */

namespace App\Repositories;

use App\Http\Models\CPanel\CPanelSeoSettings;
use Illuminate\Foundation\Http\FormRequest;

class CPanelSeoSettingsRepository extends BaseRepository
{
    public function __construct(CPanelSeoSettings $model)
    {
        parent::__construct();
        $this->model = $model;
    }

    /**
     * Always return a model instance even on a fresh DB so the settings form
     * can bind to it (singleton row id = 1).
     */
    public function firstOrNew()
    {
        return $this->model::firstOrNew(['id' => 1]);
    }

    /**
     * Persist the settings singleton (row id = 1) from validated input.
     *
     * @param  FormRequest  $request
     * @return bool
     */
    public function saveSingleton($request)
    {
        $instance = $this->model::firstOrNew(['id' => 1]);
        $instance->fill($request->validated());

        return (bool) $instance->save();
    }

    /**
     * Persist only the AEO / AI-crawler allow map on the singleton, leaving the
     * rest of the SEO settings untouched (the AEO settings tab owns this field).
     *
     * @param  array<string, bool>  $crawlers
     */
    public function saveAiCrawlers(array $crawlers): bool
    {
        $instance = $this->model::firstOrNew(['id' => 1]);
        $instance->ai_crawlers = $crawlers;

        return (bool) $instance->save();
    }
}

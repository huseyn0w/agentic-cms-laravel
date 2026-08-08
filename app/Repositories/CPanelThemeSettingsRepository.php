<?php

namespace App\Repositories;

use App\Http\Models\CPanel\CPanelThemeSettings;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Persistence for the global theme settings singleton (row id = 1).
 * Mirrors CPanelGeoSettingsRepository.
 */
class CPanelThemeSettingsRepository extends BaseRepository
{
    public function __construct(CPanelThemeSettings $model)
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
}

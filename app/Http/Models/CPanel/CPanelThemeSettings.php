<?php

namespace App\Http\Models\CPanel;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;

/**
 * Theme settings singleton (row id = 1) — tier-1 (data/token) theming.
 *
 * Mirrors CPanelGeoSettings: a single row, no timestamps, model-cached for
 * cheap reads on every front request (the public root Blade reads it to inject
 * CSS variables). See the create_theme_settings_table migration.
 */
class CPanelThemeSettings extends Model
{
    use Cachable;

    public $timestamps = false;

    protected $table = 'theme_settings';

    protected $fillable = [
        'site_title',
        'accent_color',
        'font_family',
        'radius',
    ];

    protected $casts = [
        'radius' => 'integer',
    ];
}

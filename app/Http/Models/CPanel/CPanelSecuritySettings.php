<?php

namespace App\Http\Models\CPanel;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;

/**
 * Security settings singleton (row id = 1).
 *
 * Mirrors CPanelGeoSettings: a single row, no timestamps, model-cached so the
 * login flow reads it cheaply. Drives the ThrottlesLogins concern.
 */
class CPanelSecuritySettings extends Model
{
    use Cachable;

    public $timestamps = false;

    protected $table = 'security_settings';

    protected $fillable = [
        'login_throttle_enabled',
        'login_max_attempts',
        'login_decay_minutes',
        'login_block_enabled',
        'login_block_threshold',
        'login_block_minutes',
    ];

    protected $casts = [
        'login_throttle_enabled' => 'boolean',
        'login_max_attempts' => 'integer',
        'login_decay_minutes' => 'integer',
        'login_block_enabled' => 'boolean',
        'login_block_threshold' => 'integer',
        'login_block_minutes' => 'integer',
    ];
}

<?php

namespace App\Http\Models\CPanel;

use App\Http\Models\Concerns\FlushesSettingsSingletonCache;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;

class CPanelSiteOptions extends Model
{
    use Cachable;
    use FlushesSettingsSingletonCache;

    public $timestamps = false;

    protected $table = 'site_options';

    protected $fillable = [
        'logo_url',
        'copyright',
        'github_url',
        'linkedin_url',
    ];
}

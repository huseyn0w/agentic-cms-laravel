<?php

namespace App\Http\Models\CPanel;

use App\Http\Models\Concerns\FlushesSettingsSingletonCache;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;

class CPanelGeneralSettings extends Model
{
    use Cachable;
    use FlushesSettingsSingletonCache;

    protected $table = 'general_settings';

    public $timestamps = false;

    protected $fillable = [
        'website_name',
        'tagline',
        'contact_email',
        'posts_per_page',
        'membership',
        'email_verification',
        'comments_per_page',
        'active_template_name',
        'booking_url',
    ];

    protected $casts = [
        'membership' => 'boolean',
        'email_verification' => 'boolean',
    ];
}

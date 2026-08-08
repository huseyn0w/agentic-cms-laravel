<?php

namespace App\Http\Models\CPanel;

use Illuminate\Database\Eloquent\Model;

/**
 * A single core-update attempt (audit log row). Written by the updater; read by
 * the admin updates screen to show history and any rollback.
 */
class CPanelUpdate extends Model
{
    protected $table = 'cms_updates';

    protected $fillable = [
        'from_version',
        'to_version',
        'status',
        'message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}

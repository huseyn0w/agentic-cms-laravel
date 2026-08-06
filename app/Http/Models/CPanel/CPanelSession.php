<?php

namespace App\Http\Models\CPanel;

use Illuminate\Database\Eloquent\Model;

/**
 * One row of the database session store (see the create_sessions_table
 * migration). Read-only from the app's perspective: the framework writes it,
 * we only list and delete rows for the active-sessions feature.
 */
class CPanelSession extends Model
{
    protected $table = 'sessions';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];
}

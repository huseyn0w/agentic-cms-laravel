<?php

namespace App\Http\Models\CPanel;

use App\Http\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Security audit-log row (append-only). Written by the AuthAuditSubscriber via
 * CPanelAuditLogRepository; read on the admin Security screen. No updates.
 */
class CPanelAuditLog extends Model
{
    public $timestamps = false;

    protected $table = 'security_audit_log';

    protected $fillable = [
        'action',
        'description',
        'user_id',
        'actor',
        'ip',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

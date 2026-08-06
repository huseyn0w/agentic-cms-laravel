<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A previous password hash for a user, kept so the password-reuse policy can
 * reject a new password matching one of the last N. Only created_at is tracked
 * (rows are append-only and never updated).
 */
class PasswordHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'password',
    ];

    protected $hidden = [
        'password',
    ];
}

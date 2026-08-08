<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A managed URL redirect. `source_path` is the normalized incoming path
 * (leading slash, no trailing slash, no query); `target` is a path or absolute
 * URL; `status_code` is 301 or 302.
 */
class Redirect extends Model
{
    protected $fillable = [
        'source_path',
        'target',
        'status_code',
        'hits',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'hits' => 'integer',
    ];

    /**
     * Normalize a path for consistent matching: strip the query string, ensure a
     * single leading slash, and drop any trailing slash (except the root "/").
     */
    public static function normalizePath(string $path): string
    {
        $path = explode('?', $path, 2)[0];
        $path = '/'.ltrim($path, '/');
        $path = rtrim($path, '/');

        return $path === '' ? '/' : $path;
    }
}

<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A generic Eloquent model bound to a plugin content type's table at runtime
 * (via setTable). Lets one repository serve every registered content type. Not
 * translatable, timestamps on. Mass-assignment is unguarded because the service
 * only ever passes schema-filtered, validated data.
 */
class ContentRecord extends Model
{
    protected $guarded = [];

    public $timestamps = true;

    public static function forTable(string $table): self
    {
        return (new self)->setTable($table);
    }
}

<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Per-asset media metadata (FEATURE_MATRIX §7): editorial (alt/title/caption)
 * plus technical (mime/size/width/height) fields attached to a stored file by
 * its storage-relative path. Captured on upload, editable from the media UI.
 *
 * @property string $path
 * @property ?string $mime
 * @property ?int $size
 * @property ?int $width
 * @property ?int $height
 * @property ?string $alt
 * @property ?string $title
 * @property ?string $caption
 */
class MediaMetadata extends Model
{
    protected $table = 'media_metadata';

    protected $fillable = [
        'path',
        'mime',
        'size',
        'width',
        'height',
        'alt',
        'title',
        'caption',
    ];

    protected $casts = [
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    /**
     * Upsert the metadata row for a path, merging in the given attributes.
     * Never overwrites an existing value with a null/absent one.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function forPath(string $path, array $attributes = []): self
    {
        $attributes = array_filter(
            $attributes,
            fn ($v) => $v !== null,
        );

        return static::updateOrCreate(['path' => $path], $attributes);
    }
}

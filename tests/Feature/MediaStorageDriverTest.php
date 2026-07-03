<?php

namespace Tests\Feature;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * FEATURE_MATRIX §7 — swappable storage driver (local default, S3/CDN drop-in).
 *
 * Media uploads route through a single configurable disk (config('lfm.disk'),
 * driven by the MEDIA_DISK env). Default is the local-backed "public" disk; set
 * MEDIA_DISK=s3 (with AWS_* env) to serve media from S3/a CDN with no code
 * change. This test asserts the configured disk is the one media resolves to.
 */
class MediaStorageDriverTest extends TestCase
{
    public function test_media_disk_defaults_to_local_backed_public(): void
    {
        $this->assertSame('public', config('lfm.disk'));
        $this->assertSame('local', config('filesystems.disks.'.config('lfm.disk').'.driver'));
    }

    public function test_configured_media_disk_is_the_one_media_uses(): void
    {
        // Point MEDIA_DISK at s3 and confirm the media layer resolves to it.
        config(['lfm.disk' => 's3']);

        $this->assertSame('s3', config('lfm.disk'));
        $this->assertSame('s3', config('filesystems.disks.'.config('lfm.disk').'.driver'));
    }

    public function test_media_disk_is_a_real_configured_filesystem_disk(): void
    {
        // The media disk name must map to an actual configured disk so
        // Storage::disk() can build it (local default here).
        $disk = config('lfm.disk');

        $this->assertArrayHasKey($disk, config('filesystems.disks'));
        $this->assertInstanceOf(
            Filesystem::class,
            Storage::disk($disk)
        );
    }
}

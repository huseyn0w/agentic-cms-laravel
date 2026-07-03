<?php

namespace Tests\Feature;

use App\Http\Models\MediaMetadata;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FEATURE_MATRIX §7 — per-asset media metadata.
 *
 * A lightweight model/table keyed by file path persists alt/title/caption/
 * mime/size/width/height for each media asset, captured on upload and editable
 * from the media UI (minimal edit endpoint here; richer UI is a follow-up).
 */
class MediaMetadataTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('username', 'admin')->firstOrFail();
    }

    public function test_metadata_persists_keyed_by_path(): void
    {
        $meta = MediaMetadata::query()->create([
            'path' => 'uploads/images/photo.jpg',
            'mime' => 'image/jpeg',
            'size' => 12345,
            'width' => 800,
            'height' => 600,
            'alt' => 'A photo',
            'title' => 'Photo title',
            'caption' => 'Nice caption',
        ]);

        $this->assertDatabaseHas('media_metadata', [
            'path' => 'uploads/images/photo.jpg',
            'mime' => 'image/jpeg',
            'width' => 800,
            'alt' => 'A photo',
        ]);

        $this->assertSame('image/jpeg', $meta->fresh()->mime);
    }

    public function test_for_path_upserts_metadata(): void
    {
        MediaMetadata::forPath('uploads/x.png', ['mime' => 'image/png', 'size' => 10]);
        MediaMetadata::forPath('uploads/x.png', ['alt' => 'updated alt']);

        $this->assertSame(1, MediaMetadata::where('path', 'uploads/x.png')->count());
        $row = MediaMetadata::where('path', 'uploads/x.png')->first();
        $this->assertSame('image/png', $row->mime);
        $this->assertSame('updated alt', $row->alt);
    }

    public function test_admin_can_edit_media_metadata_via_endpoint(): void
    {
        MediaMetadata::forPath('uploads/edit-me.jpg', ['mime' => 'image/jpeg']);

        $response = $this->actingAs($this->admin)->put('/cmstack-laravel-admin/media/metadata', [
            'path' => 'uploads/edit-me.jpg',
            'alt' => 'edited alt',
            'title' => 'edited title',
            'caption' => 'edited caption',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('media_metadata', [
            'path' => 'uploads/edit-me.jpg',
            'alt' => 'edited alt',
            'title' => 'edited title',
            'caption' => 'edited caption',
        ]);
    }

    public function test_metadata_edit_requires_admin(): void
    {
        $this->put('/cmstack-laravel-admin/media/metadata', [
            'path' => 'uploads/edit-me.jpg', 'alt' => 'x',
        ])->assertRedirect();
    }
}

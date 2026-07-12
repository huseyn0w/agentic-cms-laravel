<?php

namespace Tests\Feature;

use App\Support\SecureUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * FEATURE_MATRIX §21 / §7 — SVG and polyglot upload rejection.
 *
 * Target (ts canonical): reject SVG entirely; validate the file by its magic
 * bytes (not its client-supplied name/extension), and reject a file whose real
 * content does not match an allowed image/PDF type — so a polyglot mislabeled
 * as .jpg but containing SVG/HTML markup is rejected.
 */
class UploadSecurityTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = sys_get_temp_dir().'/agentic-cms-upload-test-'.uniqid();
        File::ensureDirectoryExists($this->tmpDir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tmpDir);
        parent::tearDown();
    }

    private function file(string $name, string $bytes): UploadedFile
    {
        $path = $this->tmpDir.'/'.$name;
        File::put($path, $bytes);

        return new UploadedFile($path, $name, null, null, true);
    }

    /** A minimal valid 1x1 PNG. */
    private function pngBytes(): string
    {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
        );
    }

    public function test_svg_upload_is_rejected(): void
    {
        $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';

        $this->assertFalse(SecureUpload::isAllowed($this->file('logo.svg', $svg)));
    }

    public function test_polyglot_mislabeled_as_jpg_is_rejected(): void
    {
        // SVG markup carrying a .jpg name — must be judged by bytes, not name.
        $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg"></svg>';

        $this->assertFalse(SecureUpload::isAllowed($this->file('evil.jpg', $svg)));
    }

    public function test_html_polyglot_is_rejected(): void
    {
        $html = '<!DOCTYPE html><html><body><script>alert(1)</script></body></html>';

        $this->assertFalse(SecureUpload::isAllowed($this->file('page.png', $html)));
    }

    public function test_valid_png_is_allowed(): void
    {
        $this->assertTrue(SecureUpload::isAllowed($this->file('real.png', $this->pngBytes())));
    }

    public function test_svg_is_not_in_lfm_allow_lists(): void
    {
        $this->assertNotContains('image/svg+xml', config('lfm.valid_image_mimetypes'));
        $this->assertNotContains('image/svg+xml', config('lfm.valid_file_mimetypes'));
    }

    public function test_extension_is_derived_from_validated_mime(): void
    {
        // A real PNG mislabeled with a .txt name resolves back to the png extension.
        $ext = SecureUpload::extensionFor($this->file('mystery.txt', $this->pngBytes()));

        $this->assertSame('png', $ext);
    }
}

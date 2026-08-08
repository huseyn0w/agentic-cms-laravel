<?php

namespace Tests\Feature\Updater;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Smoke: the release-packaging command is registered and wired to the builder.
 * The full packaging path (walking the tree, archiving) is covered on a fixture
 * FS by ReleaseBuilderTest; here we only assert the command exists and resolves
 * its dependencies, without archiving the whole repo.
 */
class BuildReleaseCommandTest extends TestCase
{
    public function test_the_build_release_command_is_registered(): void
    {
        $this->assertArrayHasKey('cms:build-release', Artisan::all());
    }
}

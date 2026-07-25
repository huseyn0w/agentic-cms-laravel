<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class LaravelUiRemovedTest extends TestCase
{
    public function test_no_controller_uses_laravel_ui_auth_traits(): void
    {
        $files = glob(app_path('Http/Controllers/Auth/*.php'));
        foreach ($files as $file) {
            $src = file_get_contents($file);
            foreach ([
                'AuthenticatesUsers', 'RegistersUsers', 'SendsPasswordResetEmails',
                'ResetsPasswords', 'VerifiesEmails',
            ] as $trait) {
                $this->assertStringNotContainsString(
                    $trait, $src, "$file must not use the laravel/ui trait $trait"
                );
            }
        }
    }

    public function test_composer_json_has_no_laravel_ui(): void
    {
        $composer = file_get_contents(base_path('composer.json'));
        $this->assertStringNotContainsString('laravel/ui', $composer);
    }
}

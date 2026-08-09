<?php

namespace Tests\Feature\CPanel;

use App\Http\Models\User;
use App\Services\Front\PublicShell;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * The booking_url field on the general-settings singleton: it persists through
 * the admin form, rejects a non-URL, is exposed to the admin page, and rides
 * the public shell so a theme can render a "Book a call" button.
 */
class GeneralSettingsBookingUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.ensure_pages_exist' => false]);
        $this->seed(DatabaseSeeder::class);
    }

    private function admin(): User
    {
        return User::where('username', 'admin')->firstOrFail();
    }

    /** The general-settings store keeps every existing field valid. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'website_name' => 'Site',
            'tagline' => 'Tag',
            'contact_email' => 'a@b.com',
            'posts_per_page' => 10,
            'comments_per_page' => 10,
            'membership' => false,
            'email_verification' => false,
            'active_template_name' => 'default',
        ], $overrides);
    }

    public function test_admin_page_exposes_booking_url(): void
    {
        DB::table('general_settings')->where('id', 1)->update(['booking_url' => 'https://calendly.com/elman']);

        $this->actingAs($this->admin())
            ->get(route('cpanel_general_settings'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/settings/General')
                ->where('general_settings.booking_url', 'https://calendly.com/elman'));
    }

    public function test_store_persists_a_valid_booking_url(): void
    {
        $this->actingAs($this->admin())
            ->post(route('cpanel_update_general_settings'), $this->payload([
                'booking_url' => 'https://calendly.com/elman/30min',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('general_settings', [
            'id' => 1,
            'booking_url' => 'https://calendly.com/elman/30min',
        ]);
    }

    public function test_store_rejects_a_non_url_booking_value(): void
    {
        $this->actingAs($this->admin())
            ->post(route('cpanel_update_general_settings'), $this->payload([
                'booking_url' => 'not a url',
            ]))
            ->assertSessionHasErrors('booking_url');
    }

    public function test_store_accepts_an_empty_booking_url(): void
    {
        $this->actingAs($this->admin())
            ->post(route('cpanel_update_general_settings'), $this->payload([
                'booking_url' => '',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('general_settings', [
            'id' => 1,
            'booking_url' => null,
        ]);
    }

    public function test_public_shell_exposes_the_booking_url(): void
    {
        DB::table('general_settings')->where('id', 1)->update(['booking_url' => 'https://calendly.com/elman']);

        $shell = app(PublicShell::class)->build();

        $this->assertSame('https://calendly.com/elman', $shell['general']['bookingUrl']);
    }

    public function test_public_shell_survives_the_column_missing_before_migration(): void
    {
        // Simulates an existing install where the code is live but the
        // booking_url migration has not run yet — the homepage must not 500.
        Schema::table('general_settings', function ($table) {
            $table->dropColumn('booking_url');
        });

        $shell = app(PublicShell::class)->build();

        $this->assertNull($shell['general']['bookingUrl']);
    }
}

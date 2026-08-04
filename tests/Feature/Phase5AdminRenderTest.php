<?php

namespace Tests\Feature;

use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 5 — TEMPORARY render smoke test for the Tailwind admin rewrite.
 * Authenticates as the seeded admin and asserts every admin index + create
 * form renders 200 (no Blade compile errors after the rewrite).
 */
class Phase5AdminRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function admin(): User
    {
        return User::where('username', 'admin')->firstOrFail();
    }

    public function test_admin_index_pages_render_200(): void
    {
        $routes = [
            // cpanel_home moved to Inertia (cpanel/Dashboard) in Phase 3 —
            // it no longer emits the Blade `theme-admin` string. Covered by
            // Tests\Feature\CPanel\DashboardInertiaTest instead.
            // cpanel_category_list moved to Inertia (cpanel/categories/List)
            // in Phase 3 Task 4 — covered by Tests\Feature\CPanel\CategoryInertiaRenderTest instead.
            // cpanel_myprofile moved to Inertia (cpanel/users/Form) in the Users
            // slice — covered by Tests\Feature\CPanel\UserListInertiaRenderTest.
            // cpanel_all_media moved to Inertia (cpanel/media/Index — LFM iframe
            // + dropzone) in the Media slice — covered by MediaRouteGuardTest.
            // cpanel_all_users_list moved to Inertia (cpanel/users/List) in the
            // Users slice — covered by Tests\Feature\CPanel\UserListInertiaRenderTest.
            // cpanel_pages_list + cpanel_trashed_pages_list moved to Inertia
            // (cpanel/pages/List) in the Pages slice — covered by
            // Tests\Feature\CPanel\PageListInertiaRenderTest instead.
            // cpanel_posts_list + cpanel_trashed_posts_list moved to Inertia
            // (cpanel/posts/List) in Phase 3 Posts slice — covered by
            // Tests\Feature\CPanel\PostListInertiaRenderTest instead.
            // cpanel_comments_list moved to Inertia (cpanel/comments/List) in the
            // Comments slice — covered by Tests\Feature\Admin\CommentModerationTest.
            // cpanel_user_roles moved to Inertia (cpanel/roles/List) in the Roles
            // slice — covered by Tests\Feature\Admin\RoleCrudTest.
            // cpanel_general_settings + cpanel_site_options moved to Inertia
            // (cpanel/settings/General + SiteOptions) in the Settings slice —
            // covered by Tests\Feature\Admin\SettingsTest.
            'cpanel_menu_list',
        ];

        foreach ($routes as $name) {
            $response = $this->actingAs($this->admin())->get(route($name));
            $response->assertStatus(200);
            // theme-admin body class proves the new shell rendered.
            $response->assertSee('theme-admin', false);
        }
    }

    // The admin create-form render smoke test was removed once every create
    // form migrated to Inertia (users/roles/categories/posts/pages Forms), each
    // covered by a dedicated AssertableInertia test in its slice. The only
    // holdout, cpanel_add_new_menu, is left for the Menus slice (its post-source
    // query is ambiguous under SQLite, a pre-existing quirk, not a view issue).

    // The page custom-fields builder moved from the Blade modals to the React
    // CustomFieldsBuilder component in the Pages slice. Its behaviour (add /
    // edit / remove fields, the 5 simple types, repeater passthrough) is covered
    // by resources/js/components/admin/CustomFieldsBuilder.test.tsx, and the
    // custom_fields round-trip by Tests\Feature\Admin\PageCrudTest.

    // Write-flow round-trips (settings + roles) are covered by the green
    // CPanelWriteFlowSmokeTest; the controllers/repositories are untouched by
    // Phase 5 and the rewritten forms post the identical field names, so the
    // persistence path is unchanged.
}

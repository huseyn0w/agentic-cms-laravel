<?php

namespace Tests\Unit;

use App\Http\Models\User;
use App\Http\Requests\FrontEndUserRequest;
use App\Http\Requests\ValidateUserRoles;
use App\Repositories\CPanelCategoryRepository;
use App\Repositories\CPanelPostRepository;
use App\Repositories\CPanelUserRolesRepository;
use App\Repositories\UserRepository;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Unit coverage for the repository layer against real models on sqlite:
 * whitelisting / extractData, translation switching, not-found handling and
 * the role permission rebuild.
 */
class RepositoryBehaviorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_get_returns_model_for_existing_id(): void
    {
        $repo = app(UserRepository::class);
        $user = $repo->get(1);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame(1, $user->id);
    }

    public function test_get_aborts_with_404_for_missing_id(): void
    {
        $repo = app(UserRepository::class);

        $this->expectException(HttpException::class);
        $repo->get(999999);
    }

    public function test_create_from_array_only_persists_given_attributes(): void
    {
        $repo = app(CPanelCategoryRepository::class);

        // Translatable create from a plain array (no route id) -> stored on the
        // main model, translated attributes routed to the translation table.
        $category = $repo->create([
            'title' => 'UnitCat',
            'slug' => 'unit-cat',
            'meta_description' => 'md',
            'meta_keywords' => 'mk',
            'description' => 'd',
            'author_id' => 1,
        ]);

        $this->assertNotNull($category);
        $this->assertDatabaseHas('category_translations', [
            'slug' => 'unit-cat',
            'locale' => 'en',
        ]);
    }

    public function test_post_repository_strips_non_persisted_category_field(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $this->actingAs($admin);

        $repo = app(CPanelPostRepository::class);

        // `category` is a validated input consumed by the observer, NOT a
        // posts column. The repository must strip it before mass assignment.
        $repo->create([
            'title' => 'UnitPost',
            'slug' => 'unit-post',
            'content' => 'c',
            'preview' => 'p',
            'author_id' => $admin->id,
            'meta_keywords' => 'k',
            'meta_description' => 'd',
            'status' => 1,
            'category' => [1],
        ]);

        $this->assertDatabaseHas('post_translations', ['slug' => 'unit-post']);
    }

    public function test_role_repository_rebuilds_permission_map_and_ignores_bogus_keys(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $this->actingAs($admin);

        $repo = app(CPanelUserRolesRepository::class);

        $request = Request::create('/agentic-cms-laravel-admin/roles/new', 'POST', [
            'name' => 'UnitRole',
            'permissions' => ['manage_posts', 'totally_made_up'],
        ]);
        // Reproduce a validated FormRequest by binding rules via a real one.
        $formRequest = ValidateUserRoles::createFrom($request);
        $formRequest->setContainer(app())->setRedirector(app('redirect'));
        $formRequest->validateResolved();

        $role = $repo->create($formRequest);

        $permissions = json_decode($role->permissions, true);
        $this->assertSame(1, $permissions['manage_posts']);
        $this->assertSame(0, $permissions['manage_users']);
        $this->assertArrayNotHasKey('totally_made_up', $permissions);
    }

    public function test_user_repository_update_strips_role_id_escalation(): void
    {
        $user = User::factory()->create(['role_id' => 2]);
        $this->actingAs($user);

        $repo = app(UserRepository::class);

        $request = Request::create('/profile/update', 'PUT', [
            'username' => $user->username,
            'email' => $user->email,
            'name' => 'SafeName',
            'role_id' => 1, // escalation attempt
        ]);
        $formRequest = FrontEndUserRequest::createFrom($request);
        $formRequest->setContainer(app())->setRedirector(app('redirect'));
        $formRequest->validateResolved();

        $repo->update($user->id, $formRequest);

        $fresh = $user->fresh();
        $this->assertSame('SafeName', $fresh->name);
        $this->assertSame(2, (int) $fresh->role_id, 'role_id escalation must be stripped.');
    }
}

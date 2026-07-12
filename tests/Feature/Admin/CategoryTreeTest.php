<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\CategoryTranslation;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Category hierarchy (FEATURE_MATRIX §2 "Category tree admin UI"): a category
 * can have a parent (parent_category_id, per-locale on category_translations),
 * picked from a parent dropdown that must exclude the category itself and its
 * descendants so the tree can never form a cycle.
 */
class CategoryTreeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('username', 'admin')->firstOrFail();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Travel',
            'slug' => 'travel',
            'description' => 'desc',
            'meta_description' => 'md',
            'meta_keywords' => 'mk',
            'parent_category_id' => '',
        ], $overrides);
    }

    private function create(string $title, string $slug, $parentId = ''): int
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/categories/new', $this->payload([
            'title' => $title, 'slug' => $slug, 'parent_category_id' => $parentId,
        ]));

        return CategoryTranslation::where('slug', $slug)->firstOrFail()->category_id;
    }

    public function test_creating_a_category_with_a_parent_persists_the_parent_id(): void
    {
        $parentId = $this->create('Parent', 'parent');
        $childId = $this->create('Child', 'child', $parentId);

        $child = CategoryTranslation::where('category_id', $childId)->where('locale', 'en')->firstOrFail();
        $this->assertSame($parentId, (int) $child->parent_category_id);
    }

    public function test_updating_a_category_sets_its_parent(): void
    {
        $parentId = $this->create('Parent', 'parent');
        $childId = $this->create('Child', 'child');

        $this->actingAs($this->admin)->put('/agentic-cms-laravel-admin/categories/'.$childId.'/update', $this->payload([
            'title' => 'Child', 'slug' => 'child', 'parent_category_id' => $parentId,
        ]))->assertSessionHasNoErrors();

        $child = CategoryTranslation::where('category_id', $childId)->where('locale', 'en')->firstOrFail();
        $this->assertSame($parentId, (int) $child->parent_category_id);
    }

    public function test_a_category_cannot_be_its_own_parent(): void
    {
        $id = $this->create('Solo', 'solo');

        $this->actingAs($this->admin)
            ->from('/agentic-cms-laravel-admin/categories/'.$id.'/en')
            ->put('/agentic-cms-laravel-admin/categories/'.$id.'/update', $this->payload([
                'title' => 'Solo', 'slug' => 'solo', 'parent_category_id' => $id,
            ]))
            ->assertSessionHasErrors('parent_category_id');
    }

    public function test_a_category_cannot_be_parented_to_its_own_descendant(): void
    {
        // A -> B -> C ; trying to set A's parent to C (or B) is a cycle.
        $a = $this->create('A', 'cat-a');
        $b = $this->create('B', 'cat-b', $a);
        $c = $this->create('C', 'cat-c', $b);

        $this->actingAs($this->admin)
            ->from('/agentic-cms-laravel-admin/categories/'.$a.'/en')
            ->put('/agentic-cms-laravel-admin/categories/'.$a.'/update', $this->payload([
                'title' => 'A', 'slug' => 'cat-a', 'parent_category_id' => $c,
            ]))
            ->assertSessionHasErrors('parent_category_id');

        // Sanity: C is still a descendant of A (unchanged).
        $this->assertSame($b, (int) CategoryTranslation::where('category_id', $c)->where('locale', 'en')->firstOrFail()->parent_category_id);
    }

    public function test_cycle_guard_is_not_bypassed_by_a_fractional_parent_id(): void
    {
        // "<id>.5" passes a loose numeric/notIn compare but truncates to <id> as
        // an int when persisted — the guard must normalise it first.
        $a = $this->create('A', 'cat-a');
        $b = $this->create('B', 'cat-b', $a);

        // Descendant via fraction (B is A's child).
        $this->actingAs($this->admin)
            ->from('/agentic-cms-laravel-admin/categories/'.$a.'/en')
            ->put('/agentic-cms-laravel-admin/categories/'.$a.'/update', $this->payload([
                'title' => 'A', 'slug' => 'cat-a', 'parent_category_id' => $b.'.5',
            ]))
            ->assertSessionHasErrors('parent_category_id');

        // Self via fraction.
        $this->actingAs($this->admin)
            ->from('/agentic-cms-laravel-admin/categories/'.$a.'/en')
            ->put('/agentic-cms-laravel-admin/categories/'.$a.'/update', $this->payload([
                'title' => 'A', 'slug' => 'cat-a', 'parent_category_id' => $a.'.9',
            ]))
            ->assertSessionHasErrors('parent_category_id');

        // A's parent stayed null (no cycle written).
        $this->assertNull(CategoryTranslation::where('category_id', $a)->where('locale', 'en')->firstOrFail()->parent_category_id);
    }

    public function test_parent_dropdown_excludes_self_and_descendants_on_edit(): void
    {
        $a = $this->create('Alpha', 'alpha');
        $b = $this->create('Beta', 'beta', $a);

        $html = $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/categories/'.$a.'/en')
            ->assertOk()
            ->getContent();

        // The parent <select> must not offer Alpha (self) or Beta (descendant).
        $this->assertStringNotContainsString('value="'.$a.'"', $html);
        $this->assertStringNotContainsString('value="'.$b.'"', $html);
    }
}

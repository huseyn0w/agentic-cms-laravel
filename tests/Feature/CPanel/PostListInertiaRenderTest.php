<?php

namespace Tests\Feature\CPanel;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\PostTranslation;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PostListInertiaRenderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->seed(DatabaseSeeder::class);
        config(['inertia.testing.ensure_pages_exist' => false]);
        $this->admin = User::where('username', 'admin')->firstOrFail();
    }

    private function createPost(string $title, string $slug): int
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/posts/new', [
            'title' => $title,
            'slug' => $slug,
            'content' => 'body',
            'preview' => 'p',
            'author_id' => $this->admin->id,
            'meta_keywords' => 'kw',
            'meta_description' => 'md',
            'category' => [1],
            'status' => 1,
        ]);

        return PostTranslation::where('slug', $slug)->firstOrFail()->post_id;
    }

    public function test_list_renders_inertia_component_with_shaped_rows_and_trashed_false(): void
    {
        $this->createPost('Visible post', 'visible-post');

        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/posts')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/posts/List')
                ->where('trashed', false)
                ->where('posts_list.data', function ($rows) {
                    $row = collect($rows)->firstWhere('title', 'Visible post');

                    return $row !== null
                        && array_key_exists('id', $row)
                        && array_key_exists('author', $row)
                        && array_key_exists('created_at', $row)
                        && array_key_exists('status', $row);
                }));
    }

    public function test_trashed_list_renders_the_same_component_with_trashed_true(): void
    {
        $postId = $this->createPost('Trash me', 'trash-me');
        $this->actingAs($this->admin)->delete('/agentic-cms-laravel-admin/posts/'.$postId.'/delete');

        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/posts/trashed')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/posts/List')
                ->where('trashed', true)
                ->where('posts_list.data', fn ($rows) => collect($rows)->contains('title', 'Trash me')));
    }
}

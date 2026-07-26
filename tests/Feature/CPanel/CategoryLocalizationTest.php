<?php

namespace Tests\Feature\CPanel;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Regression coverage for the Sidebar + Categories i18n gap: the React
 * components reference `cpanel/menu.*` and `cpanel/categories.*` keys that
 * previously did not exist in resources/lang/{locale}, so `tr(key, fallback)`
 * silently fell back to English for de/ru. This asserts the shared `messages`
 * dictionary (built by App\Support\I18n\TranslationDictionary and exposed by
 * HandleInertiaRequests::share()) now resolves those keys to real,
 * locale-specific strings — not the raw key, and not the English fallback.
 */
class CategoryLocalizationTest extends TestCase
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

    #[DataProvider('localeProvider')]
    public function test_categories_list_resolves_menu_and_categories_keys_for_locale(string $locale, string $expectedMenuCategories, string $expectedAddNew): void
    {
        $this->actingAs($this->admin)
            ->withSession(['locale' => $locale])
            ->get('/agentic-cms-laravel-admin/categories')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/categories/List')
                ->where('messages', function ($messages) use ($expectedMenuCategories, $expectedAddNew) {
                    $menuCategories = $messages->get('cpanel/menu.categories');
                    $addNew = $messages->get('cpanel/categories.add_new');

                    return $menuCategories === $expectedMenuCategories
                        && $menuCategories !== 'cpanel/menu.categories'
                        && $menuCategories !== 'Categories'
                        && $addNew === $expectedAddNew
                        && $addNew !== 'cpanel/categories.add_new'
                        && $addNew !== 'New category';
                }));
    }

    public static function localeProvider(): array
    {
        return [
            'de' => ['de', 'Kategorien', 'Neue Kategorie'],
            'ru' => ['ru', 'Категории', 'Новая категория'],
        ];
    }

    public function test_categories_list_resolves_menu_key_for_english_baseline(): void
    {
        $this->actingAs($this->admin)
            ->withSession(['locale' => 'en'])
            ->get('/agentic-cms-laravel-admin/categories')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('messages', fn ($messages) => $messages->get('cpanel/menu.categories') === 'Categories'));
    }
}

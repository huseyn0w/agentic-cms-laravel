<?php

namespace Tests\Feature\Front;

use App\Http\Models\PostTranslation;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class TranslationLinksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_translation_links_pick_the_entity_in_the_current_locale(): void
    {
        // Post A: en slug "shared", ru slug "obshchij-a".
        $en = PostTranslation::where('locale', 'en')->orderBy('post_id')->first();
        $en->slug = 'shared';
        $en->save();

        $ruA = PostTranslation::where('locale', 'ru')->where('post_id', $en->post_id)->first();
        $this->assertNotNull($ruA, 'post A needs a ru translation');
        $ruA->slug = 'obshchij-a';
        $ruA->save();

        // A DIFFERENT post's ru row also uses "shared" — the un-scoped lookup
        // would wrongly match this one when resolving the en page.
        $otherRu = PostTranslation::where('locale', 'ru')->where('post_id', '!=', $en->post_id)->first();
        $this->assertNotNull($otherRu, 'need a second post with a ru translation');
        $otherRu->slug = 'shared';
        $otherRu->save();

        // Viewing the en "shared" page, the ru alternate must point at post A's
        // ru slug (obshchij-a), NOT the other post that also has ru slug "shared".
        $this->get('/posts/shared')
            ->assertStatus(200)
            ->assertInertia(fn (AssertableInertia $p) => $p->where(
                'shell.languages',
                fn ($langs) => collect($langs)->contains(
                    fn ($l) => str_contains((string) ($l['url'] ?? ''), 'obshchij-a')
                )
            ));
    }
}

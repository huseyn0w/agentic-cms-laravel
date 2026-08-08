<?php

namespace Tests\Feature\Front;

use App\Http\Middleware\VerifyCsrfToken;
use App\Mail\ContactMail;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Front-end search (GET + POST + paginated), the contact form (captcha is
 * disabled in tests so it always passes) and the language switch route.
 */
class SearchContactLanguageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_search_page_renders_via_get(): void
    {
        $this->get('/search')->assertStatus(200);
    }

    public function test_search_returns_results_via_post(): void
    {
        $this->post('/search', [
            'query' => 'post',
            'filter' => 'post',
        ])->assertStatus(200);
    }

    public function test_search_requires_query_and_filter(): void
    {
        $this->from('/search')
            ->post('/search', [])
            ->assertSessionHasErrors(['query', 'filter']);
    }

    public function test_paginated_search_renders(): void
    {
        $this->get('/search/query/post/filter/post/page/1')->assertStatus(200);
    }

    public function test_contact_form_sends_mail(): void
    {
        Mail::fake();

        $this->post('/contact/sendform', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'subject' => 'Hello',
            'email' => 'jane@example.com',
            'message' => 'Test message',
        ])->assertSessionHasNoErrors();

        Mail::assertSent(ContactMail::class);
    }

    public function test_contact_form_validates_required_fields(): void
    {
        Mail::fake();

        $this->from('/contact')
            ->post('/contact/sendform', [])
            ->assertSessionHasErrors(['first_name', 'last_name', 'subject', 'email', 'message']);

        Mail::assertNothingSent();
    }

    public function test_language_switch_route_sets_locale_and_renders(): void
    {
        // The {locale?}/{slug?} front route is URL-driven: a known language
        // prefix renders directly in that locale (no session redirect).
        $this->get('/ru')
            ->assertStatus(200)
            ->assertInertia(fn (AssertableInertia $p) => $p->where('locale.current', 'ru'));
    }
}

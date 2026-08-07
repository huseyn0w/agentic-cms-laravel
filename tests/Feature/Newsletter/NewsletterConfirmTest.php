<?php

namespace Tests\Feature\Newsletter;

use App\Http\Models\NewsletterSubscriber;
use App\Mail\NewsletterConfirmationMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class NewsletterConfirmTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_valid_token_renders_confirmed_status(): void
    {
        NewsletterSubscriber::factory()->create(['token' => str_repeat('e', 64), 'status' => 'pending']);

        $this->get('/newsletter/confirm/'.str_repeat('e', 64))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $p) => $p
                ->component('public/NewsletterConfirm')
                ->where('status', 'confirmed'));

        $this->assertDatabaseHas('newsletter_subscribers', ['token' => str_repeat('e', 64), 'status' => 'confirmed']);
    }

    public function test_confirm_already_confirmed_renders_already_status(): void
    {
        NewsletterSubscriber::factory()->confirmed()->create(['token' => str_repeat('f', 64)]);

        $this->get('/newsletter/confirm/'.str_repeat('f', 64))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $p) => $p
                ->component('public/NewsletterConfirm')
                ->where('status', 'already'));
    }

    public function test_confirm_unknown_token_renders_invalid_status(): void
    {
        $this->get('/newsletter/confirm/unknown-token')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $p) => $p
                ->component('public/NewsletterConfirm')
                ->where('status', 'invalid'));
    }

    public function test_unsubscribe_valid_token_marks_unsubscribed(): void
    {
        NewsletterSubscriber::factory()->confirmed()->create(['token' => str_repeat('g', 64)]);

        $this->get('/newsletter/unsubscribe/'.str_repeat('g', 64))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $p) => $p
                ->component('public/NewsletterUnsubscribe')
                ->where('status', 'done'));

        $this->assertDatabaseHas('newsletter_subscribers', ['token' => str_repeat('g', 64), 'status' => 'unsubscribed']);
    }

    public function test_unsubscribe_unknown_token_renders_invalid(): void
    {
        $this->get('/newsletter/unsubscribe/nope')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $p) => $p
                ->component('public/NewsletterUnsubscribe')
                ->where('status', 'invalid'));
    }

    public function test_resubscribe_flips_back_to_pending_and_sends_mail(): void
    {
        Mail::fake();
        NewsletterSubscriber::factory()->unsubscribed()->create(['token' => str_repeat('h', 64)]);

        $this->post('/newsletter/resubscribe', ['token' => str_repeat('h', 64)])
            ->assertRedirect();

        $this->assertDatabaseHas('newsletter_subscribers', ['token' => str_repeat('h', 64), 'status' => 'pending']);
        Mail::assertQueued(NewsletterConfirmationMail::class);
    }
}

<?php

namespace Tests\Feature\Newsletter;

use App\Http\Models\NewsletterSubscriber;
use App\Mail\NewsletterConfirmationMail;
use App\Repositories\NewsletterSubscriberRepository;
use App\Services\Newsletter\NewsletterSubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NewsletterSubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function repo(): NewsletterSubscriberRepository
    {
        return app(NewsletterSubscriberRepository::class);
    }

    public function test_repository_creates_and_finds_by_email_and_token(): void
    {
        $sub = $this->repo()->create([
            'email' => 'reader@example.com',
            'status' => 'pending',
            'token' => str_repeat('a', 64),
            'locale' => 'en',
            'source' => 'footer',
        ]);

        $this->assertInstanceOf(NewsletterSubscriber::class, $sub);
        $this->assertTrue($sub->isPending());
        $this->assertFalse($sub->isConfirmed());

        $this->assertSame($sub->id, $this->repo()->findByEmail('reader@example.com')->id);
        $this->assertSame($sub->id, $this->repo()->findByToken(str_repeat('a', 64))->id);
        $this->assertNull($this->repo()->findByEmail('missing@example.com'));
        $this->assertNull($this->repo()->findByToken('nope'));
    }

    public function test_token_is_hidden_from_array_output(): void
    {
        $sub = $this->repo()->create([
            'email' => 'hide@example.com', 'status' => 'pending',
            'token' => str_repeat('b', 64), 'source' => 'footer',
        ]);

        $this->assertArrayNotHasKey('token', $sub->toArray());
    }

    public function test_paginate_filters_by_status_and_searches_email(): void
    {
        NewsletterSubscriber::factory()->create(['email' => 'a@keep.com', 'status' => 'confirmed']);
        NewsletterSubscriber::factory()->create(['email' => 'b@keep.com', 'status' => 'pending']);
        NewsletterSubscriber::factory()->create(['email' => 'c@other.com', 'status' => 'confirmed']);

        $confirmed = $this->repo()->paginateFiltered('confirmed', null, 10);
        $this->assertSame(2, $confirmed->total());

        $searched = $this->repo()->paginateFiltered(null, 'keep', 10);
        $this->assertSame(2, $searched->total());

        $both = $this->repo()->paginateFiltered('confirmed', 'keep', 10);
        $this->assertSame(1, $both->total());
    }

    public function test_confirmed_emails_returns_only_confirmed(): void
    {
        NewsletterSubscriber::factory()->confirmed()->create(['email' => 'yes@example.com']);
        NewsletterSubscriber::factory()->create(['email' => 'no@example.com', 'status' => 'pending']);

        $emails = $this->repo()->confirmedEmails()->pluck('email')->all();

        $this->assertSame(['yes@example.com'], $emails);
    }

    private function service(): NewsletterSubscriptionService
    {
        return app(NewsletterSubscriptionService::class);
    }

    public function test_subscribe_new_email_creates_pending_and_queues_mail(): void
    {
        Mail::fake();

        $sub = $this->service()->subscribe('new@example.com', 'footer', 'de');

        $this->assertTrue($sub->isPending());
        $this->assertSame('de', $sub->locale);
        $this->assertSame(64, strlen($sub->token));
        $this->assertDatabaseCount('newsletter_subscribers', 1);
        Mail::assertQueued(NewsletterConfirmationMail::class, fn ($m) => $m->subscriber->email === 'new@example.com');
    }

    public function test_subscribe_existing_pending_does_not_duplicate_but_resends(): void
    {
        Mail::fake();
        NewsletterSubscriber::factory()->create(['email' => 'dup@example.com', 'status' => 'pending']);

        $this->service()->subscribe('dup@example.com', 'footer', 'en');

        $this->assertDatabaseCount('newsletter_subscribers', 1);
        Mail::assertQueued(NewsletterConfirmationMail::class);
    }

    public function test_subscribe_existing_confirmed_is_noop_and_sends_nothing(): void
    {
        Mail::fake();
        NewsletterSubscriber::factory()->confirmed()->create(['email' => 'has@example.com']);

        $this->service()->subscribe('has@example.com', 'footer', 'en');

        $this->assertDatabaseCount('newsletter_subscribers', 1);
        Mail::assertNothingQueued();
    }

    public function test_subscribe_unsubscribed_reactivates_to_pending_and_sends(): void
    {
        Mail::fake();
        NewsletterSubscriber::factory()->unsubscribed()->create(['email' => 'back@example.com']);

        $sub = $this->service()->subscribe('back@example.com', 'footer', 'en');

        $this->assertTrue($sub->isPending());
        Mail::assertQueued(NewsletterConfirmationMail::class);
    }

    public function test_confirm_flips_pending_to_confirmed(): void
    {
        $sub = NewsletterSubscriber::factory()->create(['token' => str_repeat('c', 64), 'status' => 'pending']);

        $result = $this->service()->confirm(str_repeat('c', 64));

        $this->assertTrue($result->isConfirmed());
        $this->assertNotNull($result->confirmed_at);
    }

    public function test_confirm_unknown_token_returns_null(): void
    {
        $this->assertNull($this->service()->confirm('does-not-exist'));
    }

    public function test_unsubscribe_and_resubscribe_round_trip(): void
    {
        Mail::fake();
        $sub = NewsletterSubscriber::factory()->confirmed()->create(['token' => str_repeat('d', 64)]);

        $un = $this->service()->unsubscribe(str_repeat('d', 64));
        $this->assertSame('unsubscribed', $un->status);
        $this->assertNotNull($un->unsubscribed_at);

        $re = $this->service()->resubscribe(str_repeat('d', 64));
        $this->assertTrue($re->isPending());
        Mail::assertQueued(NewsletterConfirmationMail::class);
    }
}

<?php

namespace Tests\Feature\Newsletter;

use App\Http\Models\NewsletterSubscriber;
use App\Repositories\NewsletterSubscriberRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}

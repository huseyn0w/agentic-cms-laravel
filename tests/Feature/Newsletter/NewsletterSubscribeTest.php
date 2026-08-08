<?php

namespace Tests\Feature\Newsletter;

use App\Http\Models\NewsletterSubscriber;
use App\Mail\NewsletterConfirmationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NewsletterSubscribeTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmation_mail_is_queueable_and_carries_the_subscriber(): void
    {
        $sub = NewsletterSubscriber::factory()->create(['email' => 'x@example.com']);

        $mail = new NewsletterConfirmationMail($sub);

        $this->assertInstanceOf(ShouldQueue::class, $mail);
        $this->assertSame('x@example.com', $mail->subscriber->email);
    }

    public function test_subscribe_endpoint_creates_pending_and_flashes_generic_message(): void
    {
        Mail::fake();

        $this->post('/newsletter/subscribe', ['email' => 'foot@example.com'])
            ->assertRedirect()
            ->assertSessionHas('newsletter_status', 'submitted');

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'foot@example.com', 'status' => 'pending', 'source' => 'footer',
        ]);
        Mail::assertQueued(NewsletterConfirmationMail::class);
    }

    public function test_subscribe_endpoint_is_non_enumerating_for_confirmed_email(): void
    {
        Mail::fake();
        NewsletterSubscriber::factory()->confirmed()->create(['email' => 'known@example.com']);

        // Same generic outcome, no second row, no mail.
        $this->post('/newsletter/subscribe', ['email' => 'known@example.com'])
            ->assertRedirect()
            ->assertSessionHas('newsletter_status', 'submitted');

        $this->assertDatabaseCount('newsletter_subscribers', 1);
        Mail::assertNothingQueued();
    }

    public function test_honeypot_filled_is_silently_accepted_with_no_row_or_mail(): void
    {
        Mail::fake();

        $this->post('/newsletter/subscribe', ['email' => 'bot@example.com', 'website' => 'http://spam'])
            ->assertRedirect()
            ->assertSessionHas('newsletter_status', 'submitted');

        $this->assertDatabaseCount('newsletter_subscribers', 0);
        Mail::assertNothingQueued();
    }

    public function test_invalid_email_is_rejected(): void
    {
        $this->post('/newsletter/subscribe', ['email' => 'not-an-email'])
            ->assertSessionHasErrors('email');
    }

    public function test_subscribe_is_throttled_after_five_per_minute(): void
    {
        Mail::fake();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/newsletter/subscribe', ['email' => "r{$i}@example.com"])->assertRedirect();
        }

        $this->post('/newsletter/subscribe', ['email' => 'sixth@example.com'])->assertStatus(429);
    }
}

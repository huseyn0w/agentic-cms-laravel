<?php

namespace Tests\Feature\Newsletter;

use App\Http\Models\NewsletterSubscriber;
use App\Mail\NewsletterConfirmationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}

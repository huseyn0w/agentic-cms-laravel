<?php

namespace App\Mail;

use App\Http\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Double opt-in confirmation email. Queued (ShouldQueue) and localized to the
 * subscriber's captured locale by the sender (service calls ->locale(...)).
 * Reuses the same stable token for the confirm link.
 */
class NewsletterConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public NewsletterSubscriber $subscriber) {}

    public function build(): self
    {
        return $this
            ->subject(__('default/newsletter.email_subject'))
            ->markdown('emails.newsletter-confirmation', [
                'confirmUrl' => route('newsletter.confirm', $this->subscriber->token),
            ]);
    }
}

<?php

namespace App\Services\Front;

use App\Mail\ContactMail;
use App\Repositories\ContactSubmissionRepository;
use Illuminate\Support\Facades\Mail;

/**
 * Contact-form orchestration: persists the submission (so nothing is lost if
 * mail fails and the admin gets a searchable inbox), then emails the configured
 * recipient. Persistence goes through the repository — the service never touches
 * the ORM directly (arch LayeringTest). Replaces the WP Contact Form 7 +
 * Flamingo pairing.
 */
class ContactService
{
    public function __construct(private ContactSubmissionRepository $submissions) {}

    /**
     * Store a contact-form submission and send it to the configured recipient.
     */
    public function send($request): void
    {
        $data = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'subject' => $request->subject,
            'email' => $request->email,
            'message' => $request->message,
        ];

        // Persist first so a failed mail send never loses the message.
        $this->submissions->create($data + [
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        $contact_mail = get_contact_email();

        if (! $contact_mail) {
            $contact_mail = config('mail.contact_address');
        }

        Mail::to($contact_mail)->send(new ContactMail($data));
    }
}

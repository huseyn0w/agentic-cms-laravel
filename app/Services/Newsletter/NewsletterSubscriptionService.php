<?php

namespace App\Services\Newsletter;

use App\Http\Models\NewsletterSubscriber;
use App\Mail\NewsletterConfirmationMail;
use App\Repositories\NewsletterSubscriberRepository;
use Illuminate\Support\Facades\Mail;

/**
 * Domain logic for the double opt-in lifecycle. subscribe() is idempotent and
 * non-enumerating: it never tells the caller whether the email already existed,
 * and it only sends a confirmation when a confirmation is actually warranted
 * (new, still-pending, or reactivated). All persistence goes through the
 * repository.
 */
class NewsletterSubscriptionService
{
    public function __construct(private NewsletterSubscriberRepository $repo) {}

    public function subscribe(string $email, string $source, ?string $locale, ?int $userId = null): NewsletterSubscriber
    {
        $email = mb_strtolower(trim($email));
        $existing = $this->repo->findByEmail($email);

        if ($existing === null) {
            $sub = $this->repo->create([
                'email' => $email,
                'status' => NewsletterSubscriber::STATUS_PENDING,
                'token' => $this->newToken(),
                'locale' => $locale,
                'source' => $source,
                'user_id' => $userId,
            ]);

            $this->sendConfirmation($sub);

            return $sub;
        }

        // Already confirmed: nothing to do, send nothing.
        if ($existing->isConfirmed()) {
            return $existing;
        }

        // Pending or unsubscribed: (re)arm to pending and resend confirmation.
        $existing->status = NewsletterSubscriber::STATUS_PENDING;
        $existing->unsubscribed_at = null;
        if ($locale !== null) {
            $existing->locale = $locale;
        }
        $this->repo->save($existing);

        $this->sendConfirmation($existing);

        return $existing;
    }

    public function confirm(string $token): ?NewsletterSubscriber
    {
        $sub = $this->repo->findByToken($token);

        if ($sub === null) {
            return null;
        }

        if ($sub->isPending()) {
            $sub->status = NewsletterSubscriber::STATUS_CONFIRMED;
            $sub->confirmed_at = now();
            $this->repo->save($sub);
        }

        return $sub;
    }

    public function unsubscribe(string $token): ?NewsletterSubscriber
    {
        $sub = $this->repo->findByToken($token);

        if ($sub === null) {
            return null;
        }

        $sub->status = NewsletterSubscriber::STATUS_UNSUBSCRIBED;
        $sub->unsubscribed_at = now();
        $this->repo->save($sub);

        return $sub;
    }

    public function resubscribe(string $token): ?NewsletterSubscriber
    {
        $sub = $this->repo->findByToken($token);

        if ($sub === null) {
            return null;
        }

        $sub->status = NewsletterSubscriber::STATUS_PENDING;
        $sub->unsubscribed_at = null;
        $this->repo->save($sub);

        $this->sendConfirmation($sub);

        return $sub;
    }

    private function sendConfirmation(NewsletterSubscriber $sub): void
    {
        Mail::to($sub->email)
            ->locale($sub->locale ?? config('app.locale'))
            ->queue(new NewsletterConfirmationMail($sub));
    }

    private function newToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}

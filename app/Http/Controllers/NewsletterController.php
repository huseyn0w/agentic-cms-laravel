<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscribeNewsletterRequest;
use App\Services\Newsletter\NewsletterSubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public double opt-in endpoints. Thin: all lifecycle logic lives in
 * NewsletterSubscriptionService. Responses never reveal whether an email was
 * already known (no enumeration).
 */
class NewsletterController extends Controller
{
    public function __construct(private NewsletterSubscriptionService $service) {}

    public function subscribe(SubscribeNewsletterRequest $request): RedirectResponse
    {
        // Honeypot: a filled `website` field means a bot. Accept silently — no
        // row, no mail, same generic outcome so bots can't tell.
        if (! $request->filled('website')) {
            $this->service->subscribe(
                $request->validated('email'),
                'footer',
                get_current_lang(),
                $request->user()?->id,
            );
        }

        return back()
            ->with('newsletter_status', 'submitted')
            ->with('success', __('default/newsletter.check_inbox'));
    }

    public function confirm(string $token): Response
    {
        $sub = $this->service->confirm($token);

        $status = 'invalid';

        if ($sub !== null) {
            // confirm() flips pending->confirmed. If it was already confirmed
            // before this call, its status did not transition now.
            $status = $sub->wasChanged('status') ? 'confirmed' : 'already';
        }

        return Inertia::render('public/NewsletterConfirm', ['status' => $status]);
    }

    public function unsubscribe(string $token): Response
    {
        $sub = $this->service->unsubscribe($token);

        return Inertia::render('public/NewsletterUnsubscribe', [
            'status' => $sub === null ? 'invalid' : 'done',
            'token' => $sub?->token,
        ]);
    }

    public function resubscribe(Request $request): RedirectResponse
    {
        $token = (string) $request->input('token');
        $this->service->resubscribe($token);

        return back()->with('success', __('default/newsletter.unsub_resubmitted'));
    }
}

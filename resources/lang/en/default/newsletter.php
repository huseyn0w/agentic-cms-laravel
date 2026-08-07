<?php

/**
 * Public-facing newsletter strings (Phase 1): footer widget, confirm/unsubscribe
 * pages, and the double opt-in confirmation email. Consumed by the public Inertia
 * pages via react-i18next (default/newsletter.*) and by NewsletterConfirmationMail.
 */

return [
    // Footer subscribe widget
    'widget_heading' => 'Subscribe to the newsletter',
    'widget_subtitle' => 'Occasional updates. No spam. Unsubscribe anytime.',
    'widget_placeholder' => 'you@example.com',
    'widget_button' => 'Subscribe',
    'widget_submitted' => 'Thanks — check your inbox to confirm your subscription.',

    // Generic, non-enumerating flash returned by subscribe/resubscribe
    'check_inbox' => 'If that address is new, check your inbox to confirm your subscription.',

    // Confirmation email
    'email_subject' => 'Confirm your newsletter subscription',
    'email_heading' => 'Confirm your subscription',
    'email_intro' => 'You (or someone using this address) asked to subscribe to our newsletter. Confirm below to start receiving it.',
    'email_button' => 'Confirm subscription',
    'email_fallback' => 'If the button does not work, copy and paste this link into your browser:',
    'email_ignore' => 'If you did not request this, you can safely ignore this email.',

    // Confirm result page (public/NewsletterConfirm)
    'confirm_confirmed_title' => 'Subscription confirmed',
    'confirm_confirmed_body' => 'Thanks — your subscription is now active.',
    'confirm_already_title' => 'Already confirmed',
    'confirm_already_body' => 'This subscription was already confirmed. Nothing else to do.',
    'confirm_invalid_title' => 'Invalid link',
    'confirm_invalid_body' => 'This confirmation link is not valid. It may have been mistyped.',

    // Unsubscribe result page (public/NewsletterUnsubscribe)
    'unsub_done_title' => 'You have been unsubscribed',
    'unsub_done_body' => 'You will no longer receive the newsletter. Changed your mind?',
    'unsub_resubscribe_button' => 'Re-subscribe',
    'unsub_invalid_title' => 'Invalid link',
    'unsub_invalid_body' => 'This unsubscribe link is not valid.',
    'unsub_resubmitted' => 'Check your inbox to confirm your subscription again.',
];

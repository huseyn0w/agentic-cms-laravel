<?php

namespace Tests\Feature\Front;

use App\Mail\ContactMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The public contact form both stores the submission (searchable admin inbox,
 * nothing lost if mail fails) and emails the recipient. Replaces WP CF7 +
 * Flamingo.
 */
class ContactSubmissionStorageTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'subject' => 'Consulting enquiry',
            'email' => 'ada@example.com',
            'message' => 'I would like to book a call.',
        ], $overrides);
    }

    public function test_submitting_the_form_persists_a_row_and_sends_mail(): void
    {
        Mail::fake();

        $this->post(route('sendform'), $this->payload())->assertRedirect();

        $this->assertDatabaseHas('contact_submissions', [
            'email' => 'ada@example.com',
            'subject' => 'Consulting enquiry',
            'read_at' => null,
        ]);

        Mail::assertSent(ContactMail::class);
    }

    public function test_submission_is_persisted_even_when_mail_delivery_fails(): void
    {
        // Mail send throws, but the submission must already be stored.
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp down'));

        try {
            $this->post(route('sendform'), $this->payload(['email' => 'grace@example.com']));
        } catch (\Throwable) {
            // The controller doesn't catch it; we only care that the row exists.
        }

        $this->assertDatabaseHas('contact_submissions', ['email' => 'grace@example.com']);
    }

    public function test_validation_rejects_a_missing_email(): void
    {
        $this->post(route('sendform'), $this->payload(['email' => '']))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('contact_submissions', 0);
    }
}

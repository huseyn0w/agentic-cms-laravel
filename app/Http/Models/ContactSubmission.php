<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A stored contact-form submission. Written by the public form (ContactService)
 * and read in the admin inbox (CPanelContactSubmissionController). `read_at`
 * nullable = unread. Mirrors NewsletterSubscriber as a standalone, non-User,
 * non-translatable model.
 */
class ContactSubmission extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'subject',
        'message',
        'ip',
        'user_agent',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}

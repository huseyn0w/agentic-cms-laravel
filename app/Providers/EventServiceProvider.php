<?php

namespace App\Providers;

use App\Events\CommentSubmitted;
use App\Listeners\CaptureMediaMetadata;
use App\Listeners\SendCommentNotification;
use App\Listeners\SendVerificationNotificationIfEnabled;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use UniSharp\LaravelFilemanager\Events\FileWasUploaded;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendVerificationNotificationIfEnabled::class,
        ],
        CommentSubmitted::class => [
            SendCommentNotification::class,
        ],
        // Per-asset media metadata capture on upload (FEATURE_MATRIX §7).
        FileWasUploaded::class => [
            CaptureMediaMetadata::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }

    /**
     * Override Laravel's automatic email-verification listener registration.
     *
     * The framework would otherwise attach its unconditional
     * SendEmailVerificationNotification to the Registered event (because User
     * implements MustVerifyEmail), emailing every new user. We dispatch the
     * notification ourselves via SendVerificationNotificationIfEnabled, gated on
     * the "email_verification" general setting, so verification stays togglable.
     */
    protected function configureEmailVerification()
    {
        //
    }
}

<?php

namespace App\Listeners;

use App\Services\CPanel\AuditLogService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;

/**
 * Records authentication events into the security audit log. Registered as an
 * event subscriber in EventServiceProvider. The service swallows write errors,
 * so a logging fault can never break the login/logout flow it observes.
 */
class AuthAuditSubscriber
{
    public function __construct(private AuditLogService $audit) {}

    public function handleLogin(Login $event): void
    {
        $this->audit->record('login', 'Signed in', $event->user?->getAuthIdentifier(), $event->user?->username ?? null);
    }

    public function handleFailed(Failed $event): void
    {
        $attempted = $event->credentials['email'] ?? $event->credentials['username'] ?? null;
        $this->audit->record('login_failed', 'Failed login attempt', $event->user?->getAuthIdentifier(), $attempted);
    }

    public function handleLogout(Logout $event): void
    {
        $this->audit->record('logout', 'Signed out', $event->user?->getAuthIdentifier(), $event->user?->username ?? null);
    }

    public function handleLockout(Lockout $event): void
    {
        $attempted = $event->request->input('email') ?? $event->request->input('username');
        $this->audit->record('lockout', 'Too many login attempts — temporarily locked out', null, $attempted);
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Failed::class => 'handleFailed',
            Logout::class => 'handleLogout',
            Lockout::class => 'handleLockout',
        ];
    }
}

<?php

namespace App\Http\Controllers\Auth\Concerns;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Local replacement for the laravel/ui-provided
 * `Illuminate\Foundation\Auth\ThrottlesLogins` trait (removed along with the
 * laravel/ui dependency).
 *
 * The knobs (max attempts / decay) are no longer hardcoded — they are read from
 * the security_settings singleton via get_security_settings(), which falls back
 * to the shipped defaults when the row/table is absent. A second, longer-lived
 * "auto-block" counter can be enabled to hold repeat offenders for a longer
 * window after a higher failed-attempt threshold, independent of the short
 * throttle having decayed.
 */
trait ThrottlesLogins
{
    /**
     * Per-request memo of the security settings singleton (one query max).
     */
    private ?object $securitySettings = null;

    private bool $securitySettingsLoaded = false;

    /**
     * Determine if the user has too many failed login attempts. Either the
     * short throttle or (when enabled) the auto-block tier being over its limit
     * counts as locked out.
     *
     * @return bool
     */
    protected function hasTooManyLoginAttempts(Request $request)
    {
        if (! $this->throttlingEnabled()) {
            return false;
        }

        if ($this->limiter()->tooManyAttempts($this->throttleKey($request), $this->maxAttempts())) {
            return true;
        }

        return $this->blockEnabled()
            && $this->limiter()->tooManyAttempts($this->blockKey($request), $this->blockThreshold());
    }

    /**
     * Increment the login attempts for the user. Hits the short throttle key
     * and, when the auto-block tier is enabled, a separate longer-lived key.
     *
     * @return void
     */
    protected function incrementLoginAttempts(Request $request)
    {
        if (! $this->throttlingEnabled()) {
            return;
        }

        $this->limiter()->hit(
            $this->throttleKey($request), $this->decayMinutes() * 60
        );

        if ($this->blockEnabled()) {
            $this->limiter()->hit(
                $this->blockKey($request), $this->blockMinutes() * 60
            );
        }
    }

    /**
     * Redirect the user after determining they are locked out. Reports whichever
     * of the two tiers has the longest remaining time.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws ValidationException
     */
    protected function sendLockoutResponse(Request $request)
    {
        $seconds = max(
            $this->limiter()->availableIn($this->throttleKey($request)),
            $this->blockEnabled() ? $this->limiter()->availableIn($this->blockKey($request)) : 0
        );

        throw ValidationException::withMessages([
            $this->username() => [trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ])],
        ])->status(Response::HTTP_TOO_MANY_REQUESTS);
    }

    /**
     * Clear both login locks for the given user credentials.
     *
     * @return void
     */
    protected function clearLoginAttempts(Request $request)
    {
        $this->limiter()->clear($this->throttleKey($request));
        $this->limiter()->clear($this->blockKey($request));
    }

    /**
     * Fire an event when a lockout occurs.
     *
     * @return void
     */
    protected function fireLockoutEvent(Request $request)
    {
        event(new Lockout($request));
    }

    /**
     * Get the throttle key for the given request.
     *
     * @return string
     */
    protected function throttleKey(Request $request)
    {
        return Str::transliterate(Str::lower($request->input($this->username())).'|'.$request->ip());
    }

    /**
     * Get the auto-block key: a separate counter from the short throttle so the
     * two tiers accumulate and expire independently.
     *
     * @return string
     */
    protected function blockKey(Request $request)
    {
        return 'block|'.$this->throttleKey($request);
    }

    /**
     * Get the rate limiter instance.
     *
     * @return RateLimiter
     */
    protected function limiter()
    {
        return app(RateLimiter::class);
    }

    /**
     * Whether the login throttle is switched on at all. Defaults to on when no
     * settings row exists.
     */
    protected function throttlingEnabled(): bool
    {
        $value = $this->securitySetting('login_throttle_enabled', true);

        return (bool) $value;
    }

    /**
     * Whether the longer auto-block tier is switched on.
     */
    protected function blockEnabled(): bool
    {
        return (bool) $this->securitySetting('login_block_enabled', false);
    }

    /**
     * Get the maximum number of short-throttle attempts to allow.
     *
     * @return int
     */
    public function maxAttempts()
    {
        return (int) $this->securitySetting('login_max_attempts', 5);
    }

    /**
     * Get the number of minutes the short throttle locks for.
     *
     * @return int
     */
    public function decayMinutes()
    {
        return (int) $this->securitySetting('login_decay_minutes', 1);
    }

    /**
     * Failed-attempt count that trips the auto-block tier.
     */
    protected function blockThreshold(): int
    {
        return (int) $this->securitySetting('login_block_threshold', 10);
    }

    /**
     * Duration of the auto-block, in minutes.
     */
    protected function blockMinutes(): int
    {
        return (int) $this->securitySetting('login_block_minutes', 60);
    }

    /**
     * Read a single security-settings value, memoised for the request, with a
     * fallback used when the row/table is absent or the field is null.
     *
     * @param  mixed  $default
     * @return mixed
     */
    private function securitySetting(string $key, $default)
    {
        if (! $this->securitySettingsLoaded) {
            $this->securitySettings = get_security_settings();
            $this->securitySettingsLoaded = true;
        }

        $value = $this->securitySettings?->{$key};

        return $value ?? $default;
    }
}

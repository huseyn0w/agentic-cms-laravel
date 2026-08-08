<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Auth;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Decoded permission map for the authenticated user, e.g.
     * ['manage_users' => 1, 'see_admin_panel' => 0, ...].
     *
     * @var array<string, int>
     */
    private array $user_permissions = [];

    public function __construct()
    {
        $user = Auth::user();

        if (is_null($user)) {
            return;
        }

        $decoded = json_decode($user->permissions() ?? '', true);

        if (is_array($decoded)) {
            $this->user_permissions = $decoded;
        }
    }

    /**
     * Whether the current user holds the given permission flag.
     */
    private function has(string $permission): bool
    {
        return ($this->user_permissions[$permission] ?? 0) === 1;
    }

    public function manage_general_settings(): bool
    {
        return $this->has('manage_general_settings');
    }

    public function manage_users(): bool
    {
        return $this->has('manage_users');
    }

    public function manage_user_roles(): bool
    {
        return $this->has('manage_user_roles');
    }

    public function manage_pages(): bool
    {
        return $this->has('manage_pages');
    }

    public function manage_post_categories(): bool
    {
        return $this->has('manage_post_categories');
    }

    public function manage_posts(): bool
    {
        return $this->has('manage_posts');
    }

    public function manage_services(): bool
    {
        return $this->has('manage_services');
    }

    public function manage_menus(): bool
    {
        return $this->has('manage_menus');
    }

    public function manage_comments(): bool
    {
        return $this->has('manage_comments');
    }

    public function manage_media(): bool
    {
        return $this->has('manage_media');
    }

    public function manage_newsletter(): bool
    {
        return $this->has('manage_newsletter');
    }

    public function manage_updates(): bool
    {
        return $this->has('manage_updates');
    }

    public function manage_messages(): bool
    {
        return $this->has('manage_messages');
    }

    public function see_admin_panel(): bool
    {
        return $this->has('see_admin_panel');
    }
}

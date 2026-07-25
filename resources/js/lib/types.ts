// Shared prop shapes emitted by app/Http/Middleware/HandleInertiaRequests.php.
// Keep in sync with that middleware's share() method.

export interface AuthUser {
    id: number;
    name: string;
    email: string;
}

export type Ability =
    | 'see_admin_panel'
    | 'manage_users'
    | 'manage_user_roles'
    | 'manage_posts'
    | 'manage_post_categories'
    | 'manage_pages'
    | 'manage_services'
    | 'manage_menus'
    | 'manage_comments'
    | 'manage_general_settings';

export interface SharedProps {
    auth: {
        user: AuthUser | null;
        can: Record<Ability, boolean>;
    };
    locale: {
        current: string;
        available: Record<string, string> | string[];
    };
    flash: {
        status: string | null;
        success: string | null;
        error: string | null;
    };
    /** Flat UI-string dictionary for the current locale (react-i18next resources). */
    messages: Record<string, string>;
    [key: string]: unknown;
}

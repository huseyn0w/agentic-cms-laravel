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
    | 'manage_general_settings'
    | 'manage_newsletter'
    | 'manage_messages'
    | 'manage_updates';

export interface SharedProps {
    auth: {
        user: AuthUser | null;
        can: Record<Ability, boolean>;
    };
    locale: {
        current: string;
        available: Record<string, string> | string[];
    };
    /** Agentic CMS core version (config/cms.php), shown in the admin. */
    cms: {
        version: string;
        /** Version of an available update (admins with manage_updates only), or null. */
        updateAvailable?: string | null;
    };
    flash: {
        status: string | null;
        success: string | null;
        error: string | null;
        newsletter_status: string | null;
    };
    /** Flat UI-string dictionary for the current locale (react-i18next resources). */
    messages: Record<string, string>;
    [key: string]: unknown;
}

/**
 * Shape of a Laravel LengthAwarePaginator as Inertia serializes it. Meta beyond
 * data/current_page/last_page/total is optional so partial test fixtures still
 * satisfy the type.
 */
export interface Paginator<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    from?: number | null;
    to?: number | null;
    prev_page_url?: string | null;
    next_page_url?: string | null;
}

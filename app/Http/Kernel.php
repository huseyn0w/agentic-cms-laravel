<?php

namespace App\Http;

use App\Http\Middleware\AdminPanelMiddleware;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckForMaintenanceMode;
use App\Http\Middleware\EnableSsrOnPublicRoutes;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\EnforceSiteLockdown;
use App\Http\Middleware\EnsureEmailIsVerifiedWhenRequired;
use App\Http\Middleware\EnsureRegistrationEnabled;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\Localization;
use App\Http\Middleware\ManageCategories;
use App\Http\Middleware\ManageComments;
use App\Http\Middleware\ManageGeneralSettings;
use App\Http\Middleware\ManageMedia;
use App\Http\Middleware\ManageMenu;
use App\Http\Middleware\ManagePages;
use App\Http\Middleware\ManagePosts;
use App\Http\Middleware\ManageRoles;
use App\Http\Middleware\ManageServices;
use App\Http\Middleware\ManageUsers;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\RequireTwoFactorEnrollment;
use App\Http\Middleware\RestrictAdminByIp;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TrimStrings;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        CheckForMaintenanceMode::class,
        ValidatePostSize::class,
        TrimStrings::class,
        ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            Localization::class,
            // Decides whether this request gets server-rendered. Must run before
            // Inertia builds the response, which is what reads the flag it writes.
            EnableSsrOnPublicRoutes::class,
            HandleInertiaRequests::class,
            SecurityHeaders::class,
        ],

        'api' => [
            'throttle:60,1',
            'bindings',
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'manage_users' => ManageUsers::class,
        'manage_posts' => ManagePosts::class,
        'manage_roles' => ManageRoles::class,
        'manage_pages' => ManagePages::class,
        'manage_services' => ManageServices::class,
        'manage_menus' => ManageMenu::class,
        'manage_comments' => ManageComments::class,
        'manage_media' => ManageMedia::class,
        'see_admin_panel' => AdminPanelMiddleware::class,
        'manage_categories' => ManageCategories::class,
        'manage_general_settings' => ManageGeneralSettings::class,
        'require_2fa' => RequireTwoFactorEnrollment::class,
        'restrict_admin_ip' => RestrictAdminByIp::class,
        'site_lockdown' => EnforceSiteLockdown::class,
        'auth' => Authenticate::class,
        'auth.basic' => AuthenticateWithBasicAuth::class,
        'bindings' => SubstituteBindings::class,
        'cache.headers' => SetCacheHeaders::class,
        'can' => Authorize::class,
        'guest' => RedirectIfAuthenticated::class,
        'registration_enabled' => EnsureRegistrationEnabled::class,
        'signed' => ValidateSignature::class,
        'throttle' => ThrottleRequests::class,
        'verified' => EnsureEmailIsVerified::class,
        'verified_if_required' => EnsureEmailIsVerifiedWhenRequired::class,
    ];

    /**
     * The priority-sorted list of middleware.
     *
     * This forces non-global middleware to always be in the given order.
     *
     * @var array
     */
    protected $middlewarePriority = [
        StartSession::class,
        ShareErrorsFromSession::class,
        Authenticate::class,
        AuthenticateSession::class,
        SubstituteBindings::class,
        Authorize::class,
    ];
}

<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

require __DIR__.'/auth.php';

Route::get('/logout', '\App\Http\Controllers\Auth\LoginController@logout')->name('cpanel-logout');

/*
|--------------------------------------------------------------------------
| Phase 7 (SEO/GEO): public SEO endpoints
|--------------------------------------------------------------------------
| Registered before the front-end catch-all ({locale?}/{slug?}) so they are
| not swallowed by the page router.
*/
Route::get('/sitemap.xml', 'SeoController@sitemap')->name('sitemap');
Route::get('/robots.txt', 'SeoController@robots')->name('robots');
Route::get('/llms.txt', 'SeoController@llms')->name('llms');
Route::get('/rss.xml', 'SeoController@rss')->name('rss');
Route::get('/atom.xml', 'SeoController@atom')->name('atom');
// Per-category syndication feeds (FEATURE_MATRIX §16).
Route::get('/blog/category/{slug}/rss.xml', 'SeoController@categoryRss')->name('category_rss');
Route::get('/blog/category/{slug}/atom.xml', 'SeoController@categoryAtom')->name('category_atom');

/*
|--------------------------------------------------------------------------
| Health / readiness endpoints (FEATURE_MATRIX §15)
|--------------------------------------------------------------------------
| Liveness (/health) and readiness (/health/ready, with a DB probe). Kept
| ahead of the front catch-all so the page router never swallows them.
*/
Route::get('/health', 'HealthController@live')->name('health');
Route::get('/health/ready', 'HealthController@ready')->name('health_ready');

/*
|--------------------------------------------------------------------------
| Newsletter (Phase 1): public double opt-in
|--------------------------------------------------------------------------
| Registered before the front catch-all ({locale?}/{slug?}) so /newsletter/*
| is not swallowed. Outside site_lockdown so unsubscribe links always work.
*/
Route::prefix('newsletter')->group(function () {
    Route::post('/subscribe', 'NewsletterController@subscribe')
        ->middleware('throttle:5,1')->name('newsletter.subscribe');
    Route::get('/confirm/{token}', 'NewsletterController@confirm')->name('newsletter.confirm');
    Route::get('/unsubscribe/{token}', 'NewsletterController@unsubscribe')->name('newsletter.unsubscribe');
    Route::post('/resubscribe', 'NewsletterController@resubscribe')
        ->middleware('throttle:5,1')->name('newsletter.resubscribe');
});

/*
|--------------------------------------------------------------------------
| Inertia smoke route (Phase 0 of the Blade -> Inertia migration)
|--------------------------------------------------------------------------
| Temporary: proves the Inertia + React pipeline renders. Must sit above the
| front catch-all ({locale?}/{slug?}) so it is not swallowed. Remove once real
| Inertia pages exist. See ~/.claude/plans/wild-percolating-allen.md
*/
Route::get('/inertia-demo', fn () => Inertia::render('Demo', [
    'message' => 'Inertia is wired.',
]))->name('inertia_demo');

/*
|--------------------------------------------------------------------------
| Control Panel Routes
|--------------------------------------------------------------------------
*/

Route::prefix('agentic-cms-laravel-admin')->middleware(['restrict_admin_ip', 'auth', 'see_admin_panel', 'require_2fa'])->namespace('CPanel')->group(function () {

    Route::get('/locale/{locale}', 'CPanelLanguageController@index')->name('lang_route');

    Route::get('/', 'CPanelHomeController@index')->name('cpanel_home');

    Route::prefix('general-settings')->middleware('manage_general_settings')->group(function () {
        Route::get('/', 'CPanelGeneralSettingController@index')->name('cpanel_general_settings');
        Route::post('/', 'CPanelGeneralSettingController@store')->name('cpanel_update_general_settings');
    });

    Route::prefix('site-options')->middleware('manage_general_settings')->group(function () {
        Route::get('/', 'CPanelSiteOptionsController@index')->name('cpanel_site_options');
        Route::post('/', 'CPanelSiteOptionsController@store')->name('cpanel_update_site_options');
    });

    Route::prefix('seo-settings')->middleware('manage_general_settings')->group(function () {
        Route::get('/', 'CPanelSeoSettingsController@index')->name('cpanel_seo_settings');
        Route::post('/', 'CPanelSeoSettingsController@store')->name('cpanel_update_seo_settings');
    });

    Route::prefix('geo-settings')->middleware('manage_general_settings')->group(function () {
        Route::get('/', 'CPanelGeoSettingsController@index')->name('cpanel_geo_settings');
        Route::post('/', 'CPanelGeoSettingsController@store')->name('cpanel_update_geo_settings');
    });

    Route::prefix('aeo-settings')->middleware('manage_general_settings')->group(function () {
        Route::get('/', 'CPanelAeoSettingsController@index')->name('cpanel_aeo_settings');
        Route::post('/', 'CPanelAeoSettingsController@store')->name('cpanel_update_aeo_settings');
    });

    Route::prefix('theme-settings')->middleware('manage_general_settings')->group(function () {
        Route::get('/', 'CPanelThemeSettingsController@index')->name('cpanel_theme_settings');
        Route::post('/', 'CPanelThemeSettingsController@store')->name('cpanel_update_theme_settings');
    });

    Route::prefix('plugins')->middleware('manage_general_settings')->group(function () {
        Route::get('/', 'CPanelPluginController@index')->name('cpanel_plugins_list');
        Route::put('/toggle', 'CPanelPluginController@toggle')->name('cpanel_toggle_plugin');
    });

    Route::prefix('security')->middleware('manage_general_settings')->group(function () {
        Route::get('/', 'CPanelSecurityController@index')->name('cpanel_security');
        Route::post('/settings', 'CPanelSecurityController@updateSettings')->name('cpanel_update_security_settings');
    });

    // MCP connection guide: shows the server endpoint + OAuth discovery URL so
    // an admin can wire an MCP client (e.g. Claude) to the site. Auth is OAuth
    // 2.1 with dynamic client registration, so no manual tokens are issued here.
    Route::prefix('mcp')->middleware('manage_general_settings')->group(function () {
        Route::get('/', 'CPanelMcpController@index')->name('cpanel_mcp');
    });

    // Core updates: WordPress-style in-admin core update. Shows the current
    // version + availability and runs the updater. Gated by manage_updates.
    Route::prefix('updates')->middleware('manage_updates')->group(function () {
        Route::get('/', 'CPanelUpdateController@index')->name('cpanel_updates');
        Route::post('/check', 'CPanelUpdateController@check')->name('cpanel_updates_check');
        Route::post('/run', 'CPanelUpdateController@run')->name('cpanel_updates_run');
    });

    // The profile controller resolves the user from Auth when no id is
    // supplied (see CPanelUserController::editUser), so this route can only
    // ever surface the authenticated user's own profile.
    Route::prefix('myprofile')->group(function () {
        Route::get('/', 'CPanelUserController@editUser')->name('cpanel_myprofile');
        // Active browser sessions (self-service). The service scopes every
        // action to the current user, so no per-resource permission is needed.
        Route::delete('/sessions/{id}', 'CPanelSessionController@revoke')->name('cpanel_revoke_session');
        Route::post('/sessions/logout-others', 'CPanelSessionController@logoutOthers')->name('cpanel_logout_other_sessions');
    });

    Route::prefix('users')->middleware('manage_users')->group(function () {
        Route::get('/', 'CPanelUserController@index')->name('cpanel_all_users_list');
        Route::get('/{id}', 'CPanelUserController@editUser')->name('cpanel_edit_user_profile')->where('id', '[0-9]+');
        Route::put('/{id}/update', 'CPanelUserController@updateUser')->name('cpanel_update_user_profile')->where('id', '[0-9]+');
        Route::delete('/{id}/delete', 'CPanelUserController@deleteAjax')->name('cpanel_delete_user')->where('id', '[0-9]+');
        Route::delete('/multipleDelete', 'CPanelUserController@multipleDelete')->name('cpanel_users_bulk_delete');
        Route::get('/new', 'CPanelUserController@addUser')->name('cpanel_add_new_user');
        Route::post('/new', 'CPanelUserController@createUser')->name('cpanel_save_new_user');
    });

    Route::prefix('roles')->middleware('manage_roles')->group(function () {
        Route::get('/', 'CPanelRoleController@index')->name('cpanel_user_roles');
        Route::get('/{id}', 'CPanelRoleController@editRole')->name('cpanel_edit_user_role')->where('id', '[0-9]+');
        Route::put('/{id}/update', 'CPanelRoleController@updateRole')->name('cpanel_update_user_role')->where('id', '[0-9]+');
        Route::delete('/{id}/delete', 'CPanelRoleController@deleteRole')->name('cpanel_delete_user_role')->where('id', '[0-9]+');
        Route::get('/new', 'CPanelRoleController@addRole')->name('cpanel_add_user_role');
        Route::post('/new', 'CPanelRoleController@createRole')->name('cpanel_save_user_role');
    });

    Route::prefix('pages')->middleware('manage_pages')->group(function () {
        Route::get('/', 'CPanelPageController@index')->name('cpanel_pages_list');
        Route::get('/trashed', 'CPanelPageController@trashedPages')->name('cpanel_trashed_pages_list');
        // Restore is GET /{id}/restore — registered BEFORE the greedy /{id}/{lang}
        // editor route so it isn't shadowed (matched as edit with lang="restore").
        Route::get('/{id}/restore', 'CPanelPageController@restore')->name('cpanel_restore_page')->where('id', '[0-9]+');
        // Admin preview (GET /{id}/preview) — before the greedy /{id}/{lang}
        // editor route, or it is shadowed as editPage(lang="preview").
        Route::get('/{id}/preview', [PageController::class, 'preview'])->name('cpanel_preview_page')->where('id', '[0-9]+');
        Route::get('/{id}/{lang}', 'CPanelPageController@editPage')->name('cpanel_edit_page')->where('id', '[0-9]+');
        Route::put('/{id}/update', 'CPanelPageController@updatePage')->name('cpanel_update_page')->where('id', '[0-9]+');
        Route::get('/{id}/revisions/{lang}', 'CPanelPageController@revisions')->name('cpanel_page_revisions')->where('id', '[0-9]+');
        Route::get('/{id}/revisions/{revision}/compare/{lang}', 'CPanelPageController@revisionDiff')->name('cpanel_page_revision_diff')->where(['id' => '[0-9]+', 'revision' => '[0-9]+']);
        Route::post('/{id}/revisions/{revision}/restore/{lang}', 'CPanelPageController@restoreRevision')->name('cpanel_restore_page_revision')->where(['id' => '[0-9]+', 'revision' => '[0-9]+']);
        Route::delete('/multipleDelete', 'CPanelPageController@multipleDelete')->name('cpanel_pages_bulk_delete');
        Route::post('/multiple', 'CPanelPageController@multipleActions')->name('cpanel_pages_bulk_action');
        Route::delete('/{id}/destroy', 'CPanelPageController@destroyAjax')->name('cpanel_destroy_page')->where('id', '[0-9]+');
        Route::delete('/{id}/delete', 'CPanelPageController@deleteAjax')->name('cpanel_ajax_soft_delete_page')->where('id', '[0-9]+');
        Route::get('/new', 'CPanelPageController@addPage')->name('cpanel_add_new_page');
        Route::post('/new/{id?}', 'CPanelPageController@createPage')->name('cpanel_save_new_page')->where('id', '[0-9]+');
    });

    Route::prefix('services')->middleware('manage_services')->group(function () {
        Route::get('/', 'CPanelServiceController@index')->name('cpanel_services_list');
        Route::get('/trashed', 'CPanelServiceController@trashedServices')->name('cpanel_trashed_services_list');
        // Restore is GET /{id}/restore — registered BEFORE the greedy /{id}/{lang}
        // editor route (and guarded by a numeric id) so it isn't shadowed.
        Route::get('/{id}/restore', 'CPanelServiceController@restore')->name('cpanel_restore_service')->where('id', '[0-9]+');
        Route::get('/{id}/{lang}', 'CPanelServiceController@editService')->name('cpanel_edit_service')->where('id', '[0-9]+');
        Route::put('/{id}/update', 'CPanelServiceController@updateService')->name('cpanel_update_service')->where('id', '[0-9]+');
        Route::post('/multiple', 'CPanelServiceController@multipleActions')->name('cpanel_services_bulk_action');
        Route::delete('/{id}/destroy', 'CPanelServiceController@destroyAjax')->name('cpanel_destroy_service')->where('id', '[0-9]+');
        Route::delete('/{id}/delete', 'CPanelServiceController@deleteAjax')->name('cpanel_ajax_soft_delete_service')->where('id', '[0-9]+');
        Route::get('/new', 'CPanelServiceController@addService')->name('cpanel_add_new_service');
        Route::post('/new/{id?}', 'CPanelServiceController@createService')->name('cpanel_save_new_service')->where('id', '[0-9]+');
    });

    Route::prefix('categories')->middleware('manage_categories')->group(function () {
        Route::get('/', 'CPanelCategoryController@index')->name('cpanel_category_list');
        Route::get('/{id}/{lang}', 'CPanelCategoryController@edit')->name('cpanel_edit_category')->where('id', '[0-9]+');
        Route::put('/{id}/update', 'CPanelCategoryController@updateCategory')->name('cpanel_update_category')->where('id', '[0-9]+');
        Route::delete('/multipleDelete', 'CPanelCategoryController@multipleDelete')->name('cpanel_category_bulk_delete');
        Route::delete('/{id}/delete', 'CPanelCategoryController@deleteAjax')->name('cpanel_ajax_soft_delete_category')->where('id', '[0-9]+');
        Route::get('/new', 'CPanelCategoryController@addCategory')->name('cpanel_add_new_category');
        Route::post('/new/{id?}', 'CPanelCategoryController@createCategory')->name('cpanel_save_new_category')->where('id', '[0-9]+');
    });

    Route::prefix('posts')->middleware('manage_posts')->group(function () {
        Route::get('/', 'CPanelPostController@index')->name('cpanel_posts_list');
        Route::get('/trashed', 'CPanelPostController@trashedPosts')->name('cpanel_trashed_posts_list');
        // Restore (GET /{id}/restore) must precede the greedy /{id}/{lang} editor
        // route, or it is shadowed (matched as editPost with lang="restore").
        Route::get('/{id}/restore', 'CPanelPostController@restore')->name('cpanel_restore_post')->where('id', '[0-9]+');
        // Admin preview (GET /{id}/preview) renders the public post page for a
        // draft/scheduled post; like restore, it must precede the greedy
        // /{id}/{lang} editor route or it is shadowed as editPost(lang="preview").
        Route::get('/{id}/preview', [PostController::class, 'preview'])->name('cpanel_preview_post')->where('id', '[0-9]+');
        Route::get('/{id}/{lang}', 'CPanelPostController@editPost')->name('cpanel_edit_post')->where('id', '[0-9]+');
        Route::put('/{id}/update', 'CPanelPostController@updatePost')->name('cpanel_update_post')->where('id', '[0-9]+');
        Route::get('/{id}/revisions/{lang}', 'CPanelPostController@revisions')->name('cpanel_post_revisions')->where('id', '[0-9]+');
        Route::get('/{id}/revisions/{revision}/compare/{lang}', 'CPanelPostController@revisionDiff')->name('cpanel_post_revision_diff')->where(['id' => '[0-9]+', 'revision' => '[0-9]+']);
        Route::post('/{id}/revisions/{revision}/restore/{lang}', 'CPanelPostController@restoreRevision')->name('cpanel_restore_post_revision')->where(['id' => '[0-9]+', 'revision' => '[0-9]+']);
        Route::delete('/{id}/destroy', 'CPanelPostController@destroyAjax')->name('cpanel_destroy_post')->where('id', '[0-9]+');
        Route::delete('/multipleDelete', 'CPanelPostController@multipleDelete')->name('cpanel_posts_bulk_delete');
        Route::post('/multiple', 'CPanelPostController@multipleActions')->name('cpanel_posts_bulk_action');
        Route::delete('/{id}/delete', 'CPanelPostController@deleteAjax')->name('cpanel_ajax_soft_delete_post')->where('id', '[0-9]+');
        Route::get('/new', 'CPanelPostController@addPost')->name('cpanel_add_new_post');
        Route::post('/new/{id?}', 'CPanelPostController@createPost')->name('cpanel_save_new_post')->where('id', '[0-9]+');
    });

    Route::prefix('menus')->middleware('manage_menus')->group(function () {
        Route::get('/', 'CPanelMenuController@index')->name('cpanel_menu_list');
        Route::get('/{id}/{lang}', 'CPanelMenuController@editMenu')->name('cpanel_edit_menu')->where('id', '[0-9]+');
        Route::put('/{id}/update', 'CPanelMenuController@updateMenu')->name('cpanel_update_menu')->where('id', '[0-9]+');
        Route::delete('/multipleDelete', 'CPanelMenuController@multipleDelete')->name('cpanel_menus_bulk_delete');
        Route::delete('/{id}/delete', 'CPanelMenuController@deleteMenu')->name('cpanel_ajax_soft_delete_menu')->where('id', '[0-9]+');
        Route::get('/new', 'CPanelMenuController@addMenu')->name('cpanel_add_new_menu');
        Route::post('/new/{id?}', 'CPanelMenuController@createMenu')->name('cpanel_save_new_menu')->where('id', '[0-9]+');
    });

    Route::prefix('comments')->middleware('manage_comments')->group(function () {
        Route::get('/', 'CPanelCommentController@index')->name('cpanel_comments_list');
        Route::put('/{id}/approve', 'CPanelCommentController@approve')->name('cpanel_approve_comment')->where('id', '[0-9]+');
        Route::put('/{id}/unapprove', 'CPanelCommentController@unApprove')->name('cpanel_unapprove_comment')->where('id', '[0-9]+');
        Route::delete('/{id}/delete', 'CPanelCommentController@deleteAjax')->name('cpanel_delete_comment')->where('id', '[0-9]+');
        Route::delete('/multipleDelete', 'CPanelCommentController@multipleDelete')->name('cpanel_comments_bulk_delete');
    });

    // Media management is gated behind its own manage_media capability
    // (seeded permission + roles UI; existing Administrator roles backfilled by
    // the add-manage-media migration so they keep access).
    Route::prefix('media')->middleware('manage_media')->group(function () {
        Route::get('/', 'CPanelMediaController@index')->name('cpanel_all_media');
        // Per-asset media metadata (FEATURE_MATRIX §7): read + edit alt/title/caption.
        Route::get('/metadata', 'CPanelMediaController@metadata')->name('cpanel_media_metadata');
        Route::put('/metadata', 'CPanelMediaController@updateMetadata')->name('cpanel_update_media_metadata');
    });

    Route::prefix('newsletter')->middleware('manage_newsletter')->group(function () {
        Route::get('/', 'CPanelNewsletterController@index')->name('cpanel_newsletter_list');
        Route::get('/export', 'CPanelNewsletterController@export')->name('cpanel_newsletter_export');
        Route::post('/', 'CPanelNewsletterController@store')->name('cpanel_newsletter_store');
        Route::delete('/{id}', 'CPanelNewsletterController@destroy')->name('cpanel_newsletter_destroy')->where('id', '[0-9]+');
    });

    // Generic CRUD for plugin content types. One route group serves every
    // registered type by {type} slug; the controller resolves it from the
    // registry (404 if not registered / plugin disabled).
    Route::prefix('content/{type}')->middleware('manage_content')->group(function () {
        Route::get('/', 'CPanelContentController@index')->name('cpanel_content_index');
        Route::get('/create', 'CPanelContentController@createForm')->name('cpanel_content_create');
        Route::post('/', 'CPanelContentController@store')->name('cpanel_content_store');
        Route::get('/{id}/edit', 'CPanelContentController@editForm')->name('cpanel_content_edit')->where('id', '[0-9]+');
        Route::put('/{id}', 'CPanelContentController@updateItem')->name('cpanel_content_update')->where('id', '[0-9]+');
        Route::delete('/{id}', 'CPanelContentController@destroy')->name('cpanel_content_destroy')->where('id', '[0-9]+');
    })->where('type', '[a-z0-9-]+');

    // Managed redirects (301/302) for old URLs — SEO-safe migration.
    Route::prefix('redirects')->middleware('manage_general_settings')->group(function () {
        Route::get('/', 'CPanelRedirectController@index')->name('cpanel_redirects');
        Route::post('/', 'CPanelRedirectController@store')->name('cpanel_redirects_store');
        Route::delete('/{id}', 'CPanelRedirectController@destroy')->name('cpanel_redirects_destroy')->where('id', '[0-9]+');
    });

    // Contact-form inbox: stored submissions from the public contact form.
    Route::prefix('contact')->middleware('manage_messages')->group(function () {
        Route::get('/', 'CPanelContactSubmissionController@index')->name('cpanel_contact_list');
        Route::get('/{id}', 'CPanelContactSubmissionController@show')->name('cpanel_contact_show')->where('id', '[0-9]+');
        Route::delete('/{id}', 'CPanelContactSubmissionController@destroy')->name('cpanel_contact_destroy')->where('id', '[0-9]+');
    });

});

/*
|--------------------------------------------------------------------------
| Front End Routes
|--------------------------------------------------------------------------
*/

Route::group(['middleware' => 'site_lockdown'], function () {

    Route::prefix('search')->group(function () {
        Route::get('/', 'PageController@search')->name('get_search_page');
        Route::post('/', 'PageController@searchResult')->name('get_search_result');
        Route::get('/query/{searchstring}/filter/{searchtype}/page/{page}', 'PageController@paginatedResult')->name('search_result_paginated');
    });

    Route::get('/{locale?}/posts/{slug}', 'PostController@languageIndex')->name('posts_localized');

    Route::prefix('/posts')->group(function () {

        Route::get('/{slug}', 'PostController@index')->name('posts');
        Route::post('/handlelike/{id}', 'PostController@handleLike')->middleware(['auth', 'verified_if_required'])->name('handle_post_likes')->where('id', '[1-9]+[0-9]*');
        Route::put('/handlecomment/', 'PostCommentController@update')->middleware(['auth', 'verified_if_required', 'throttle:8,1'])->name('update_post_comment');
        Route::post('/handlecomment/{id}', 'PostCommentController@store')->middleware(['auth', 'verified_if_required', 'throttle:8,1'])->name('store_post_comments')->where('id', '[1-9]+[0-9]*');
        Route::delete('/deletecomment/{id}', 'PostCommentController@delete')->middleware(['auth', 'verified_if_required', 'throttle:8,1'])->name('delete_post_comments')->where('id', '[1-9]+[0-9]*');

    });

    // Service content type — public listing + detail.
    // The non-prefixed /services routes MUST come before the optional-locale
    // variants so the router does not fall through to the catch-all
    // {locale?}/{slug?} (known Laravel optional-segment routing quirk).
    // Names live on the non-locale variants so route() helpers work without params.
    Route::prefix('/services')->group(function () {
        Route::get('/{slug}', 'ServiceController@show')->name('services_show');
        Route::get('/', 'ServiceController@listing')->name('services_index');
    });
    Route::get('/{locale}/services/{slug}', 'ServiceController@show');
    Route::get('/{locale}/services', 'ServiceController@listing');

    Route::post('/contact/sendform', 'PageController@sendMail')->name('sendform');

    Route::get('/{locale?}/category/{slug}', 'CategoryController@languageIndex')->name('categories_localized');

    Route::prefix('/category')->group(function () {
        Route::get('/{slug}', 'CategoryController@index')->name('categories_first_page');
        Route::get('/{slug}/page/{page?}', 'CategoryController@index')->name('categories_display_pages')->where('page', '[1-9]+[0-9]*');
    });

    Route::get('/{locale?}/tag/{slug}', 'TagController@languageIndex')->name('tags_localized');

    Route::prefix('/tag')->group(function () {
        Route::get('/{slug}', 'TagController@index')->name('tags_first_page');
        Route::get('/{slug}/page/{page?}', 'TagController@index')->name('tags_display_pages')->where('page', '[1-9]+[0-9]*');
    });

    Route::prefix('profile')->middleware(['auth', 'verified_if_required'])->group(function () {
        Route::get('/edit', 'UserController@yourProfile')->name('get_user_info');
        Route::put('/update', 'UserController@update')->name('update_user_info');
        Route::get('/change_password', 'UserController@password')->name('get_change_password_interface');
        Route::put('/change_password', 'UserController@changePassword')->name('change_password_action');
    });

    Route::get('/users/{username}', 'UserController@show')->name('show_user');

    Route::get('login/{provider}', 'Auth\LoginController@redirectToProvider')->where('provider', 'google');
    Route::get('login/{provider}/callback', 'Auth\LoginController@handleProviderCallback')->where('provider', 'google');

    Route::get('/{locale?}/{slug?}', 'PageController@languageIndex')->name('front_pages');

});

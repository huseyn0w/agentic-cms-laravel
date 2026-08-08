<?php

namespace App\Http\Controllers;

class BaseController extends Controller
{
    /**
     * Domain VIEW service (a App\Services\BaseCrudService subclass) that owns all
     * data access for the front-end cluster. Controllers never call a repository
     * directly — the chain is Controller -> Service -> Repository. Left untyped
     * (like CPanelBaseController) so each subclass can assign its own concrete
     * domain service and call domain-specific methods on it.
     */
    protected $service;

    protected $data;

    public function __construct()
    {
        $this->lang_prefixes = get_lang_prefixes();
    }

    protected function index(string $slug, ?string $locale = null)
    {
        if (is_null($locale)) {
            $locale = get_current_lang();
        }

        $slug = $this->modifyTranslatedSlug($locale, $slug);

        $this->data = $this->service->resolveBySlug($slug);

        if (is_null($this->data)) {
            throwNotFound();
        }

        \Session::put('slug', $slug);

        return true;
    }

    /**
     * Catch-all disambiguation for /{locale?}/{slug?}: when the first segment is
     * NOT a language prefix and no slug was given, treat that segment as the slug
     * (a default-locale page like /about). The locale itself is already resolved
     * from the URL by the Localization middleware, so there is no language
     * switch or redirect here.
     */
    protected function modifyTranslatedSlug($locale, $slug)
    {
        if (! in_array($locale, $this->lang_prefixes) && $slug === '/') {
            $slug = $locale;
        }

        return $slug;
    }

    protected function languageIndex($locale, string $slug = '/')
    {
        return $this->index($slug, $locale);
    }
}

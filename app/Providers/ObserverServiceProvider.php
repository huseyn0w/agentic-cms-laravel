<?php

namespace App\Providers;

use App\Http\Models\Page;
use App\Http\Models\PageTranslation;
use App\Http\Models\Post;
use App\Http\Models\PostTranslation;
use App\Http\Models\ServiceTranslation;
use App\Http\Models\User;
use App\Observers\PageObserver;
use App\Observers\PageTranslationObserver;
use App\Observers\PostObserver;
use App\Observers\PostTranslationObserver;
use App\Observers\ServiceTranslationObserver;
use App\Observers\UserObserver;
use Illuminate\Support\ServiceProvider;

class ObserverServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Post::observe(PostObserver::class);
        PostTranslation::observe(PostTranslationObserver::class);
        Page::observe(PageObserver::class);
        PageTranslation::observe(PageTranslationObserver::class);
        ServiceTranslation::observe(ServiceTranslationObserver::class);
        User::observe(UserObserver::class);
    }
}

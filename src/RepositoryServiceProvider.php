<?php

namespace Mblarsen\LaravelRepository;

use Illuminate\Support\ServiceProvider;

class LaravelRepositoryServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Bind the default ResourceContext
        $this->app->bind(ResourceContext::class, RequestResourceContext::class);
    }
}

<?php

namespace Mblarsen\LaravelRepository;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->environment('testing')) {
            $this->loadMigrationsFrom([__DIR__ . '/../tests/migrations']);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Bind the default ResourceContext if not bound by user already
        if (!$this->app->bound(ResourceContext::class)) {
            $this->app->bind(ResourceContext::class, RequestResourceContext::class);
        }
    }
}

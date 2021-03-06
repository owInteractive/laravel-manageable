<?php

namespace Ow\Manageable;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootRoutes();

        $this->publishes([
            __DIR__ . '/config.php' => config_path('manageble.php'),
        ], 'config');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config.php', 'manageable');

        $this->app->bind('manageable', function ($app) {
            return new ManageableManager();
        });
    }

    protected function bootRoutes()
    {
        // $this->loadRoutesFrom(__DIR__ . '/routes.php');

        $this->publishes([
            __DIR__ . '/routes/manageable.php' => base_path('routes/manageble.php'),
        ], 'routes');

        // Tries to get the routes configuration from the routes/manageable.php
    }
}

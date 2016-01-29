<?php

namespace NikhilPandey\TpLink;

use Illuminate\Support\ServiceProvider;

class TpLinkServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {

    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // Bind to the IoC Container
        $this->app->singleton('tplink', function ($app) {
            return new Router;
        });
    }
}

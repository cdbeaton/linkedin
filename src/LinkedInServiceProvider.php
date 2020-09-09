<?php

namespace Cdbeaton\Linkedin;

use Illuminate\Support\ServiceProvider;

class LinkedInServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->make('Cdbeaton\Linkedin\LinkedInController');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}

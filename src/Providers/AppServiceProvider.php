<?php

namespace Bdc\Installer\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->publishFiles();
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Views', 'views');

    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

    }

    protected function publishFiles()
    {
        $this->publishes([
            __DIR__.'/../assets' => public_path('vendor'),
        ]);
        $this->publishes([
            __DIR__.'/../Views' => base_path('resources/views/vendor/bdc/installer'),
        ]);
    }
}

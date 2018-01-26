<?php

namespace Protechstudio\PrestashopWebService;

use Illuminate\Support\ServiceProvider;

class PrestashopWebServiceProvider extends ServiceProvider
{

    protected $defer = true;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publish();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConfig();

        $this->app->singleton(PrestashopWebService::class, function () {
            return new PrestashopWebService(
                config('prestashop-webservice.url'),
                config('prestashop-webservice.token'),
                config('prestashop-webservice.debug')
            );
        });
    }

    public function provides()
    {
        return ['Protechstudio\PrestashopWebService\PrestashopWebService'];
    }

    private function publish()
    {
        $this->publishes([
            __DIR__ . '/config.php' => config_path('prestashop-webservice.php'),
        ], 'config');
    }

    private function registerConfig()
    {
        $this->mergeConfigFrom(__DIR__ . '/config.php', 'prestashop-webservice');
    }
}

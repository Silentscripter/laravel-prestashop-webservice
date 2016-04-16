<?php

namespace Protechstudio\PrestashopWebService;

use Illuminate\Support\ServiceProvider;
use PrestaShopWebservice;

class PrestashopWebServiceProvider extends ServiceProvider
{

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

        $this->app->singleton(PrestaShopWebservice::class, function () {
            return new PrestaShopWebservice(config('prestashop-webservice.url'),
                config('prestashop-webservice.token', config('prestashop-webservice.debug')));
        });
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

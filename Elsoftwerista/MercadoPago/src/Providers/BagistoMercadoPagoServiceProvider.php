<?php

namespace Elsoftwerista\MercadoPago\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Elsoftwerista\MercadoPago\Contracts\MercadoPagoPlusOrder;

/**
 * BagistoMercadoPago service provider
 *
 * @author Claudio Godoi <cgodoi@Elsoftwerista.cl>
 * @copyright 2023 Elsoftwerista (https://www.Elsoftwerista.cl)
 */
class BagistoMercadoPagoServiceProvider extends ServiceProvider
{
    /**
    * Bootstrap services.
    *
    * @return void
    */
    public function boot()
    {
        include __DIR__ . '/../Http/routes.php';
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'mercadopago');
        $this->loadMigrationsFrom(__DIR__ .'/../Database/Migrations');
        $this->app->register(ModuleServiceProvider::class);
    }

    /**
    * Register services.
    *
    * @return void
    */
    public function register()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/menu.php',
            'menu.admin'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/system.php',
            'core'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/paymentmethods.php',
            'paymentmethods'
        );

    }
}

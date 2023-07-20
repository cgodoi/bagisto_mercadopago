<?php

namespace Elsoftwerista\MercadoPago\Providers;

use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        \Elsoftwerista\MercadoPago\Models\MercadoPagoPlusOrder::class
    ];
}

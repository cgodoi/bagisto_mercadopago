<?php

namespace Elsoftwerista\MercadoPago\Repositories;

use Webkul\Core\Eloquent\Repository;

class MercadoPagoPlusOrderRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model()
    {
        return 'Elsoftwerista\MercadoPago\Contracts\MercadoPagoPlusOrder';
    }
}

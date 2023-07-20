<?php

namespace Elsoftwerista\MercadoPago\Payment;

use Illuminate\Support\Facades\Config;
use Webkul\Payment\Payment\Payment;

/**
 * MercadoPago payment method class
 *
 * @author   Claudio Godoi <cgodoi@Elsoftwerista.cl>
 * @copyright 2023
 */
class MercadoPagoPlus extends Payment
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $code  = 'mercadopago_plus';


    public function getRedirectUrl()
    {
        return route('mercadopago_plus.redirect');
    }
}

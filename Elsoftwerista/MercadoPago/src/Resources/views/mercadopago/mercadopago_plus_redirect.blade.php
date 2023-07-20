@php
    require '../vendor/autoload.php';
    // Agrega credenciales
    $MercadoPagoPlus = app('Elsoftwerista\MercadoPago\Payment\MercadoPagoPlus');
    $MercadoPagoPlusRepository = app('Elsoftwerista\MercadoPago\Repositories\MercadoPagoPlusOrderRepository');
    $onProduction = (bool) $MercadoPagoPlus->getConfigData('production');
    //carro
    $cart = $MercadoPagoPlus->getCart();

    //creamos la orden
    $orderRepository = app('Webkul\Sales\Repositories\OrderRepository');
    $order = $orderRepository->create(Cart::prepareDataForOrder());

    $access_token = $MercadoPagoPlus->getConfigData('access_token');
    $public_key = $MercadoPagoPlus->getConfigData('public_key');
    MercadoPago\SDK::setAccessToken($access_token);

    $preference = new MercadoPago\Preference();
    $return_url = route('mercadopago_plus.ipn');
    $cancel_url = route('mercadopago_plus.cancel');
    //urls de retorno
    $preference->back_urls = [
        'success' => $return_url,
        'failure' => $return_url,
        'pending' => $return_url,
    ];
    $preference->auto_return = "approved";
    $preference->external_reference = $order->id;

    // Crea un ítem en la preferencia
    $item = new MercadoPago\Item();
    $item->title = 'Mi compra';
    $item->quantity = 1;
    $item->unit_price = $cart['grand_total'];
    $preference->items = [$item];
    $preference->save();
    $redirect_url = '';
    //check for errors
    if (!$preference->id) {
        Log::error('Error en mercado pago preference', [var_export($preference, true)]);
        $redirect_url = $cancel_url;
    } else {
        //redirect url segun ambiente
        if (!$onProduction) {
            $redirect_url = $preference->sandbox_init_point;
        } else {
            $redirect_url = $preference->init_point;
        }

        $mDate = new DateTime();
        $mUniqid = uniqid($mDate->getTimestamp());

        //Creamos registro de pago para posterior relacion
        $MercadoPagoPlusRepository->create([
            'total_amount' => $cart['grand_total'],
            'transaction_detail' => JSON_ENCODE(['token' => $preference->id, 'preference' => $preference->toArray()]),
            'status' => 'pending',
            'order_id' => $order->id,
        ]);
    }

    header('Location: ' . $redirect_url, true, 302);
    exit;
@endphp


<body data-gr-c-s-loaded="true" cz-shortcut-listen="true">
    <div class="container">
        Se te redireccionará a MercadoPago en unos segundos...
    </div>
</body>

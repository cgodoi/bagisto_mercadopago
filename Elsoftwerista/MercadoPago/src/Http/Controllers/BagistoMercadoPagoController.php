<?php

namespace Elsoftwerista\MercadoPago\Http\Controllers;
// require '../vendor/autoload.php';
use Illuminate\Http\Request;
use Elsoftwerista\MercadoPago\Repositories\MercadoPagoPlusOrderRepository;
use Webkul\Checkout\Facades\Cart;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\ShipmentRepository;
use Illuminate\Support\Facades\Http;

class BagistoMercadoPagoController extends Controller
{
    protected $_config;

    /**
     * OrderRepository object
     *
     * @var \Webkul\Sales\Repositories\OrderRepository
     */
    protected $orderRepository;

    /**
     * OrderRepository object
     *
     * @var \Webkul\Sales\Repositories\InvoiceRepository
     */
    protected $invoiceRepository;

    protected $transaction;


    /**
     * Order $order
     *
     * @var \Webkul\Sales\Contracts\Order
     */
    protected $order;

    /**
     * ShipmentRepository object
     *
     * @var \Webkul\Sales\Repositories\ShipmentRepository
     */
    protected $shipmentRepository;

    public function __construct(
        OrderRepository $orderRepository,
        MercadoPagoPlusOrderRepository $mercadoPagoPlusRepository,
        InvoiceRepository $invoiceRepository,
        ShipmentRepository $shipmentRepository
    ) {
        $this->_config = request('_config');

        $this->orderRepository = $orderRepository;
        $this->mercadoPagoPlusRepository = $mercadoPagoPlusRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->shipmentRepository = $shipmentRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view($this->_config['view']);
    }

    /**
     * Redirects to the paypal.
     *
     * @return \Illuminate\View\View
     */
    public function redirect()
    {
        return view('mercadopago::mercadopago.mercadopago_plus_redirect');
    }

    /**
     * Cancel payment from paypal.
     *
     * @return \Illuminate\Http\Response
     */
    public function cancel()
    {
        session()->flash('error', 'El pago vía MercadoPago ha sido cancelado.');

        return redirect()->route('shop.checkout.cart.index');
    }

    /**
     * Success payment
     *
     * @return \Illuminate\Http\Response
     */
    public function success()
    {
        session()->flash('success', 'Tu pago vía MercadoPago se ha procesado correctamente.');

        return redirect()->route('shop.checkout.success');
    }

    /**
     * Prepares order's invoice data for creation.
     *
     * @return array
     */
    protected function prepareInvoiceData($order)
    {
        $invoiceData = ['order_id' => $order->id];

        foreach ($order->items as $item) {
            $invoiceData['invoice']['items'][$item->id] = $item->qty_ordered;
        }

        return $invoiceData;
    }

    /**
     * Prepares order's invoice data for creation.
     *
     * @return array
     */
    protected function prepareShipmentData($order, $sourceId)
    {
        $shipmentData = ['order_id' => $order->id];
        $shipmentData['shipment']['carrier_title'] = '';
        $shipmentData['shipment']['track_number'] = '';
        $shipmentData['shipment']['source'] = $sourceId;
        //$data['vendor_id']

        foreach ($order->items as $item) {
            $shipmentData['shipment']['items'][$item->id][$sourceId] = $item->qty_ordered;
        }

        //dd($shipmentData);
        return $shipmentData;
    }

    public function getMercadoPagoPreferance($preferanceId, $access_token)
    {

        \MercadoPago\SDK::setAccessToken($access_token);
        return \MercadoPago\Preference::find_by_id($preferanceId);
    }

    public function getMercadoPagoPayment($paymentId, $access_token)
    {

        \MercadoPago\SDK::setAccessToken($access_token);
        return \MercadoPago\Payment::find_by_id($paymentId);
    }

    /**
     * MercadoPago notification listener
     *
     * @return \Illuminate\Http\Response
     */
    public function notification()
    {
        \MercadoPago\SDK::setAccessToken("ENV_ACCESS_TOKEN");
        $merchant_order = null;
        switch ($_GET["topic"]) {
            case "payment":
                $mercadoPagoPayment = \MercadoPago\Payment::find_by_id($_GET["id"]);
                // Get the payment and the corresponding merchant_order reported by the IPN.
                $merchant_order = \MercadoPago\MerchantOrder::find_by_id($mercadoPagoPayment->order->id);
                break;
        }
        $paid_amount = 0;
        foreach ($merchant_order->payments as $payment) {
            if ($payment['status'] == 'approved') {
                $paid_amount += $payment['transaction_amount'];
            }
        }

        // Acá ya estamos seguros de que tenemos un flujo de pago normal. Si no, habría "muerto" en los checks anteriores.
        try {
            if ($mercadoPagoPayment) {
                if ($mercadoPagoPayment->status == 'approved') {
                    //Si el pago está aprobado (responseCode == 0 && status === 'AUTHORIZED') entonces aprobamos nuestra compra
                    // Código para aprobar compra acá

                    return $this->approveOrder($mercadoPagoPayment);
                } else {
                    return  $this->cancelOrder(null, $mercadoPagoPayment);
                }
            }
        } catch (\Throwable $th) {
            \Log::error("MercadoPago notification", [$th]);
        }
    }

    /**
     * MercadoPago Ipn listener
     *
     * @return \Illuminate\Http\Response
     */
    public function ipn()
    {
        $MercadoPagoPlus = app('Elsoftwerista\MercadoPago\Payment\MercadoPagoPlus');
        $onProduction = (bool) $MercadoPagoPlus->getConfigData('production');

        $status = $_GET['status'] ?? $_POST['status'] ?? null;
        $paymentId = $_GET['payment_id'] ?? $_POST['payment_id'] ?? null;
        $preferanceId = $_GET['preference_id'] ?? $_POST['preference_id'] ?? null;
        $access_token = $MercadoPagoPlus->getConfigData('access_token');

        $mercadoPagoPreference = $this->getMercadoPagoPreferance($preferanceId, $access_token);
        $mercadoPagoPayment = $this->getMercadoPagoPayment($paymentId, $access_token);

        $token = $mercadoPagoPreference->id;

        if ($this->userAbortedOnMercadoPagoForm()) {
            $this->cancelOrdenByToken($token);
            return $this->cancelOrder('Has cancelado la transacción en el formulario de pago. Intenta nuevamente', null);
            //exit('Has cancelado la transacción en el formulario de pago. Intenta nuevamente');
        }
        if ($this->anErrorOcurredOnMercadoPagoForm($mercadoPagoPayment)) {
            $this->cancelOrdenByToken($token);
            return $this->cancelOrder('Al parecer ocurrió un error en el formulario de pago. Intenta nuevamente', null);
            //exit('Al parecer ocurrió un error en el formulario de pago. Intenta nuevamente');
        }
        if ($this->theUserWasRedirectedBecauseWasIdleFor10MinutesOnMercadoPagoForm($mercadoPagoPayment)) {
            $this->cancelOrdenByToken($token);
            return $this->cancelOrder('La transacción ha sido cancelada en MercadoPago.', null);
            //exit('Superaste el tiempo máximo que puedes estar en el formulario de pago (10 minutos). La transacción fue cancelada por MercadoPago. ');
        }
        //Por último, verificamos que solo tengamos un token_ws. Si no es así, es porque algo extraño ocurre.
        if (!$this->isANormalPaymentFlow($mercadoPagoPayment)) { // Notar que dice ! al principio.
            $this->cancelOrdenByToken($token);
            return $this->cancelOrder();
            //exit('En este punto, si NO es un flujo de pago normal es porque hay algo extraño y es mejor abortar. Quizás alguien intenta llamar a esta URL directamente o algo así...');
        }

        // Acá ya estamos seguros de que tenemos un flujo de pago normal. Si no, habría "muerto" en los checks anteriores.
        try {

            if ($mercadoPagoPayment->status == 'approved') {
                //Si el pago está aprobado (responseCode == 0 && status === 'AUTHORIZED') entonces aprobamos nuestra compra
                // Código para aprobar compra acá

                return $this->approveOrder($mercadoPagoPayment, false);
            } else {
                return  $this->cancelOrder(null, $mercadoPagoPayment, false);
            }
        } catch (\Throwable $th) {
            \Log::error("MercadoPago ipn", [$th]);
            $this->cancelOrdenByToken($token);
            return $this->cancelOrder();
        }

        return;
    }

    function cancelOrder($message = null, $mercadoPagoPayment = null, $redirect = true)
    {
        // Acá has lo que tengas que hacer para marcar la orden como fallida o cancelada
        if ($mercadoPagoPayment) {

            if ($mercadoPagoPayment->external_reference) {

                if (!$this->orderIsPending($mercadoPagoPayment)) {
                    session()->flash('error', 'Ha ocurrido un problema, si fue aplicado un cobro a su cuenta comuníquese con nosotros. ');
                    return redirect()->route('shop.checkout.cart.index');
                }

                $this->orderRepository->cancel($mercadoPagoPayment->external_reference);

                $this->mercadoPagoPlusRepository->findOneWhere(['order_id' => $mercadoPagoPayment->external_reference])->update([
                    'status' => 'canceled',
                    'response_detail' => $mercadoPagoPayment->toArray()
                ]);
            }
        }
        if ($redirect) {
            if ($message) {
                session()->flash('error', $message);
            } else {
                session()->flash('error', 'El pago vía MercadoPago ha sido cancelado.');
            }


            return redirect()->route('shop.checkout.cart.index');
        }
    }

    function cancelOrdenByToken($token)
    {
        // Acá has lo que tangas que hacer para marcar la orden como aprobada o finalizada o lo que necesites en tu negocio.,
        // Update order
        try {
            $transactions = $this->mercadoPagoPlusRepository->where('transaction_detail', 'LIKE', "%{$token}%")->get();

            foreach ($transactions as $transaction) {
                //cancela orden relacionada al token
                if ($transaction->status == 'pending') {
                    $this->orderRepository->cancel($transaction->order_id);
                }
            }
            //cancela transacciones de MercadoPago
            $this->mercadoPagoPlusRepository->where('transaction_detail', 'LIKE', "%{$token}%")->update([
                'status' => 'canceled'
            ]);
        } catch (\Throwable $th) {
            //throw $th;
            \Log::error("MercadoPago cancelOrdenByToken", [$th]);
        }
    }

    function orderIsPending($mercadoPagoPayment)
    {
        $order = $this->orderRepository->find($mercadoPagoPayment->external_reference);

        return $order && $order->status == 'pending';
    }

    function approveOrder($mercadoPagoPayment, $redirect = true)
    {
        // Acá has lo que tangas que hacer para marcar la orden como aprobada o finalizada o lo que necesites en tu negocio.,
        // Update order
        $order = $this->orderRepository->find($mercadoPagoPayment->external_reference);

        $MercadoPagoPlus = app('Elsoftwerista\MercadoPago\Payment\MercadoPagoPlus');
        $defaultSourceId = (int) $MercadoPagoPlus->getConfigData('source_id');

        //create shipment
        if ($order->canShip()) {
            $shipment = $this->shipmentRepository->create($this->prepareShipmentData($order, $defaultSourceId), 'processing');
        }

        //$this->orderRepository->update(['status' => 'processing'], $order->id);
        /* if ($order->canInvoice()) {
                    $invoice = $this->invoiceRepository->create($this->prepareInvoiceData($order));
                }*/

        //actualiza estado de operacion MercadoPago
        $this->mercadoPagoPlusRepository->findOneWhere(['order_id' => $mercadoPagoPayment->external_reference])->update([
            'status' => 'completed',
            'response_detail' => $mercadoPagoPayment->toArray()
        ]);

        if ($redirect) {
            Cart::deActivateCart();

            //session()->flash('success', 'Tu pago vía MercadoPago se ha procesado correctamente.');
            session()->flash('order', (object)$order->toArray());
            session()->flash('webpay_response', [
                'store' => core()->getConfigData('sales.shipping.origin.store_name'),
                'amount' => number_format($mercadoPagoPayment->transaction_amount, 0, '.', ','),
                'cardNumber' => $mercadoPagoPayment->card->last_four_digits,
                'buyOrder' => $mercadoPagoPayment->external_reference,
                'authorizationCode' => $mercadoPagoPayment->order->id,
                'transactionDate' => date("d/m/Y H:i:s", strtotime($mercadoPagoPayment->date_created))
            ]);
            return redirect()->route('shop.checkout.success');
        }
    }

    function userAbortedOnMercadoPagoForm()
    {
        $status = $_GET['status'] ?? $_POST['status'] ?? null;
        $tbkToken = $_GET['payment_id'] ?? $_POST['payment_id'] ?? null;

        // Si estatus es nulo y payment es nulo, entonces el usuario cancelo el pago
        return !$tbkToken && !$status;
    }

    function anErrorOcurredOnMercadoPagoForm($mercadoPagoPayment)
    {
        return $mercadoPagoPayment && !$mercadoPagoPayment->status;
    }

    function theUserWasRedirectedBecauseWasIdleFor10MinutesOnMercadoPagoForm($mercadoPagoPayment)
    {
        return !$mercadoPagoPayment;
    }

    function isANormalPaymentFlow($mercadoPagoPayment)
    {
        // Si viene solo token_ws es porque es un flujo de pago normal
        return $mercadoPagoPayment && $mercadoPagoPayment->id && $mercadoPagoPayment->status && $mercadoPagoPayment->order;
    }
}

<?php

namespace Elsoftwerista\MercadoPago\Models;

use Illuminate\Database\Eloquent\Model;
use Elsoftwerista\MercadoPago\Contracts\MercadoPagoPlusOrder as MercadoPagoPlusOrderContract;
use Webkul\Sales\Models\Order as Order;

class MercadoPagoPlusOrder extends Model implements MercadoPagoPlusOrderContract
{
    protected $table = 'mercadopago_plus_orders';

    protected $fillable = [
        'total_amount', 'transaction_detail', 'status',
        'order_id', 'created_at', 'updated_at','response_detail'
    ];

    protected $statusLabel = [
        'pending' => 'Pendiente',
        'processing' => 'Pendiente',
        'completed' => 'Completo',
        'canceled' => 'Cancelado',
        'refunded' => 'Devuelto'
    ];

    public function getStatusLabelAttribute()
    {
        return $this->statusLabel[$this->status];
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}

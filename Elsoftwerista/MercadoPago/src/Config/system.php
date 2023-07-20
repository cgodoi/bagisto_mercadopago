<?php

return [
    [
        'key'  => 'sales',
        'name' => 'admin::app.admin.system.sales',
        'sort' => 1
    ], [
        'key'  => 'sales.paymentmethods',
        'name' => 'admin::app.admin.system.payment-methods',
        'sort' => 2,
    ], [
        'key'    => 'sales.paymentmethods.mercadopago_plus',
        'name'   => 'MercadoPago',
        'sort'   => 1,
        'fields' => [
            [
                'name' => 'title',
                'title' => 'admin::app.admin.system.title',
                'type' => 'text',
                'validation' => 'required',
                'channel_based' => false,
                'locale_based'  => true,
            ], [
                'name' => 'description',
                'title' => 'admin::app.admin.system.description',
                'type' => 'textarea',
                'channel_based' => false,
                'locale_based' => true,
            ], [
                'name' => 'active',
                'title' => 'Activo',
                'type' => 'boolean',
                'validation' => 'required',
                'channel_based' => true,
                'locale_based'  => true,
            ],[
                'name' => 'store_name',
                'title' => 'Nombre de la tienda',
                'type' => 'text',
                'validation' => 'required',
                'channel_based' => false,
                'locale_based' => true,
            ]
            ,[
                'name' => 'client_id',
                'title' => 'Cliente ID',
                'type' => 'text',

                'channel_based' => false,
                'locale_based' => true,
            ]    ,[
                'name' => 'client_secret',
                'title' => 'Cliente Secret',
                'type' => 'text',

                'channel_based' => false,
                'locale_based' => true,
            ]
            ,[
                'name' => 'public_key',
                'title' => 'Public Key',
                'type' => 'text',
                'validation' => 'required',
                'channel_based' => false,
                'locale_based' => true,
            ]
            ,[
                'name' => 'access_token',
                'title' => 'Access Token',
                'type' => 'text',
                'validation' => 'required',
                'channel_based' => false,
                'locale_based' => true,
            ]
            ,[
                'name' => 'source_id',
                'title' => 'Inventario ID Defecto',
                'type' => 'text',
                'channel_based' => false,
                'locale_based' => true,
            ],
             [
                'name' => 'production',
                'title' => 'Modo Producción',
                'type' => 'boolean',
                'validation' => 'required',
                'channel_based' => true,
                'locale_based'  => true,
            ], [
                'name' => 'sort',
                'title' => 'admin::app.admin.system.sort_order',
                'type' => 'select',
                'options' => [
                    [
                        'title' => '1',
                        'value' => 1
                    ], [
                        'title' => '2',
                        'value' => 2
                    ], [
                        'title' => '3',
                        'value' => 3
                    ], [
                        'title' => '4',
                        'value' => 4,
                    ]
                ],
            ]
        ],
    ],

];

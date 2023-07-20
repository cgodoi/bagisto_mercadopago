<?php

Route::group(['middleware' => ['web']], function () {
    Route::prefix('mercadopago/plus')->group(function () {
        Route::get('redirect', 'Elsoftwerista\MercadoPago\Http\Controllers\BagistoMercadoPagoController@redirect')->name('mercadopago_plus.redirect');
        Route::get('/cancel', 'Elsoftwerista\MercadoPago\Http\Controllers\BagistoMercadoPagoController@cancel')->name('mercadopago_plus.cancel');
    });
});

Route::post('mercadopago/plus/success', 'Elsoftwerista\MercadoPago\Http\Controllers\BagistoMercadoPagoController@success')->name('mercadopago_plus.success');
Route::post('mercadopago/plus/ipn', 'Elsoftwerista\MercadoPago\Http\Controllers\BagistoMercadoPagoController@ipn')->name('mercadopago_plus.ipn');
Route::get('mercadopago/plus/ipn', 'Elsoftwerista\MercadoPago\Http\Controllers\BagistoMercadoPagoController@ipn')->name('mercadopago_plus.ipn.get');
Route::get('mercadopago/plus/notification', 'Elsoftwerista\MercadoPago\Http\Controllers\BagistoMercadoPagoController@notification')->name('mercadopago_plus.notification');

<?php

Route::prefix('payment-gateway')->namespace('PaymentGateway')->group(function () {
    Route::post('xendit-callback/invoice-created', 'XenditCallbackController@invoiceCreated');
});

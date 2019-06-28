<?php

namespace App\Http\Controllers\Api\PaymentGateway;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class XenditCallbackController extends Controller
{
    public function invoiceCreated(Request $request)
    {
        log_object($request->all());
    }
}

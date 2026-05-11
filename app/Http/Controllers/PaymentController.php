<?php

namespace App\Http\Controllers;

use App\Services\Payment\PaymentManager;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function notify(string $gateway, Request $request, PaymentManager $payments)
    {
        $order = $payments->completeByNotify($gateway, $request->all());

        return response($order ? 'success' : 'fail', $order ? 200 : 400);
    }
}

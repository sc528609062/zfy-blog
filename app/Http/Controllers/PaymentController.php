<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\PaymentManager;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function notify(string $gateway, Request $request, PaymentManager $payments)
    {
        $order = $payments->completeByNotify($gateway, $request->all());

        return response($order ? 'success' : 'fail', $order ? 200 : 400);
    }

    public function query(Payment $payment, PaymentManager $payments)
    {
        return response()->json([
            'code' => 0,
            'message' => 'ok',
            'data' => $payments->queryPayment($payment),
        ]);
    }

    public function status(Order $order)
    {
        return response()->json([
            'code' => 0,
            'message' => 'ok',
            'data' => [
                'order_no' => $order->order_no,
                'status' => $order->status,
                'paid_amount' => $order->paid_amount,
                'paid_at' => $order->paid_at,
            ],
        ]);
    }
}

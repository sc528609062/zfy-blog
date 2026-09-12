<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\PaymentManager;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function qr(Request $request, Payment $payment)
    {
        abort_unless($request->user() && $payment->order?->user_id === $request->user()->id, 403);
        $value = data_get($payment->response_payload, 'qr_code');
        abort_unless(is_string($value) && $value !== '', 404);
        $code = QrCode::create($value)->setSize(280)->setMargin(12);
        $result = (new PngWriter)->write($code);

        return response($result->getString())->header('Content-Type', 'image/png')->header('Cache-Control', 'private, no-store');
    }

    public function notify(string $gateway, Request $request, PaymentManager $payments)
    {
        $payload = $gateway === 'wechat_official' ? ['body' => $request->getContent(), 'headers' => $request->headers->all()] : $request->all();
        $order = $payments->completeByNotify($gateway, $payload);

        if ($gateway === 'wechat_official') {
            return response()->json(['code' => $order ? 'SUCCESS' : 'FAIL', 'message' => $order ? '成功' : '校验失败'], $order ? 200 : 400);
        }

        return response($order ? 'success' : 'fail', $order ? 200 : 400);
    }

    public function query(Request $request, Payment $payment, PaymentManager $payments)
    {
        abort_unless($request->user() && ($payment->order?->user_id === $request->user()->id || $request->user()->can('manage commerce')), 403);
        $result = $payments->queryPayment($payment);
        if (! $request->expectsJson()) {
            return redirect('/user/orders')->with('status', $result['status'] === 'paid' ? '支付已确认。' : '尚未确认到账。');
        }

        return response()->json([
            'code' => 0,
            'message' => 'ok',
            'data' => $result,
        ]);
    }

    public function status(Request $request, Order $order)
    {
        abort_unless($request->user() && ($order->user_id === $request->user()->id || $request->user()->can('manage commerce')), 403);

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

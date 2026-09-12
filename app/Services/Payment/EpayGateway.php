<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class EpayGateway extends AbstractGateway implements ConfigurableGateway
{
    public function assertConfigured(): void
    {
        $config = config('payments.epay');
        if (! filled($config['pid']) || ! filled($config['key']) || ! filter_var($config['url'], FILTER_VALIDATE_URL) || ! str_starts_with($config['url'], 'https://')) {
            throw ValidationException::withMessages(['gateway' => '请先配置易支付 HTTPS 地址、商户号和密钥。']);
        }
    }

    public function createPayment(Order $order): Payment
    {
        $this->assertConfigured();
        $config = config('payments.epay');
        $payload = [
            'pid' => (string) $config['pid'],
            'type' => $config['type'],
            'out_trade_no' => $order->order_no,
            'notify_url' => route('payments.notify', 'epay'),
            'return_url' => url('/user/orders'),
            'name' => $order->items()->first()?->title ?? $order->order_no,
            'money' => bcadd((string) $order->total_amount, '0', 2),
        ];
        $payload['sign'] = $this->sign($payload);
        $payload['sign_type'] = 'MD5';

        return $this->rememberPayment($order, $payload, ['checkout_url' => rtrim($config['url'], '/').'/submit.php?'.http_build_query($payload)]);
    }

    public function verifyNotify(array $payload): bool
    {
        if (! filled(config('payments.epay.key')) || ! is_string($payload['sign'] ?? null) || ($payload['trade_status'] ?? null) !== 'TRADE_SUCCESS') {
            return false;
        }
        if ((string) ($payload['pid'] ?? '') !== (string) config('payments.epay.pid') || ($payload['sign_type'] ?? 'MD5') !== 'MD5') {
            return false;
        }
        foreach ($payload as $value) {
            if (! is_scalar($value) && $value !== null) {
                return false;
            }
        }

        return filled($payload['out_trade_no'] ?? null) && filled($payload['trade_no'] ?? null)
            && preg_match('/^\d+(?:\.\d{1,2})?$/', (string) ($payload['money'] ?? ''))
            && hash_equals($this->sign($payload), strtolower($payload['sign']));
    }

    public function query(Payment $payment): array
    {
        $config = config('payments.epay');
        if (! filled($config['key']) || ! filled($config['pid']) || ! str_starts_with((string) $config['url'], 'https://')) {
            throw ValidationException::withMessages(['gateway' => '在线支付尚未配置。']);
        }
        $response = Http::timeout(10)->withOptions(['allow_redirects' => false])->get(rtrim($config['url'], '/').'/api.php', [
            'act' => 'order', 'pid' => $config['pid'], 'key' => $config['key'], 'out_trade_no' => $payment->order->order_no,
        ])->throw()->json();
        if (! is_array($response) || (int) ($response['code'] ?? 0) !== 1) {
            throw ValidationException::withMessages(['payment' => '支付渠道暂时无法返回订单信息。']);
        }
        if ((string) ($response['out_trade_no'] ?? '') !== $payment->order->order_no || ! preg_match('/^\d+(?:\.\d{1,2})?$/', (string) ($response['money'] ?? '')) || bccomp((string) $response['money'], (string) $payment->amount, 2) !== 0) {
            throw ValidationException::withMessages(['payment' => '渠道订单与本地订单不一致。']);
        }
        if ((int) ($response['status'] ?? 0) === 1) {
            $payload = ['pid' => (string) $config['pid'], 'out_trade_no' => $response['out_trade_no'], 'trade_no' => $response['trade_no'] ?? '', 'money' => $response['money'], 'trade_status' => 'TRADE_SUCCESS'];
            $payload['sign'] = $this->sign($payload);
            app(PaymentManager::class)->completeByNotify($this->code(), $payload);
        }

        return ['status' => $payment->fresh()->status, 'gateway' => $this->code(), 'source' => 'gateway'];
    }

    private function sign(array $payload): string
    {
        unset($payload['sign'], $payload['sign_type']);
        $payload = array_filter($payload, fn ($value) => $value !== '' && $value !== null);
        ksort($payload);

        return md5(implode('&', array_map(fn ($key) => $key.'='.$payload[$key], array_keys($payload))).config('payments.epay.key'));
    }

    public function code(): string
    {
        return 'epay';
    }

    public function displayName(): string
    {
        return '易支付';
    }
}

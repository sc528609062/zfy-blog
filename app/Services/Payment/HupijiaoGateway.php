<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class HupijiaoGateway extends AbstractGateway implements ConfigurableGateway, VerifiedNotificationGateway
{
    public function createPayment(Order $order): Payment
    {
        $this->assertConfigured();
        $payload = ['version' => '1.1', 'lang' => 'zh-cn', 'plugins' => 'zfy_blog', 'appid' => config('payments.hupijiao.appid'), 'trade_order_id' => $order->order_no, 'payment' => config('payments.hupijiao.payment'), 'total_fee' => bcadd((string) $order->total_amount, '0', 2), 'title' => mb_substr($order->items()->first()?->title ?? $order->order_no, 0, 32), 'time' => time(), 'notify_url' => route('payments.notify', $this->code()), 'return_url' => url('/user/orders'), 'callback_url' => url('/user/orders'), 'nonce_str' => bin2hex(random_bytes(16))];
        $response = $this->request('do', $payload);
        $url = $response['url'] ?? '';
        if (! is_string($url) || ! str_starts_with($url, 'https://') || ! filter_var($url, FILTER_VALIDATE_URL)) {
            $this->fail('渠道未返回有效付款地址。');
        }

        return $this->rememberPayment($order, $payload, ['checkout_url' => $url, 'channel_order_id' => $response['openid'] ?? null]);
    }

    public function verifyNotify(array $payload): bool
    {
        return $this->notification($payload) !== null;
    }

    public function notification(array $payload): ?array
    {
        if (! $this->validSignature($payload) || ($payload['status'] ?? '') !== 'OD' || ($payload['plugins'] ?? '') !== 'zfy_blog') {
            return null;
        }
        if (isset($payload['appid']) && (string) $payload['appid'] !== (string) config('payments.hupijiao.appid')) {
            return null;
        }

        return ['out_trade_no' => $payload['trade_order_id'] ?? '', 'trade_no' => $payload['transaction_id'] ?? '', 'money' => $payload['total_fee'] ?? ''];
    }

    public function query(Payment $payment): array
    {
        $response = $this->request('query', ['appid' => config('payments.hupijiao.appid'), 'trade_order_id' => $payment->order->order_no, 'time' => time(), 'nonce_str' => bin2hex(random_bytes(16))]);
        if (($response['trade_order_id'] ?? '') !== $payment->order->order_no) {
            $this->fail('渠道订单不匹配。');
        }
        if (($response['status'] ?? '') === 'OD') {
            app(PaymentManager::class)->completeVerified($this->code(), ['out_trade_no' => $response['trade_order_id'], 'trade_no' => $response['transaction_id'] ?? '', 'money' => $response['total_fee'] ?? '']);
        }

        return ['status' => $payment->fresh()->status, 'gateway' => $this->code(), 'source' => 'gateway'];
    }

    private function request(string $action, array $payload): array
    {
        $this->assertConfigured();
        $payload['hash'] = $this->sign($payload);
        $response = Http::asForm()->timeout(15)->withOptions(['allow_redirects' => false])->post(rtrim(config('payments.hupijiao.url'), '/').'/'.$action.'.html', $payload)->throw()->json();
        if (! is_array($response) || ! $this->validSignature($response) || (int) ($response['errcode'] ?? -1) !== 0) {
            $this->fail('虎皮椒响应校验失败。');
        }

        return $response;
    }

    private function validSignature(array $payload): bool
    {
        if (! filled(config('payments.hupijiao.secret')) || ! is_string($payload['hash'] ?? null)) {
            return false;
        }
        foreach ($payload as $value) {
            if (! is_scalar($value) && $value !== null) {
                return false;
            }
        }

        return hash_equals($this->sign($payload), strtolower($payload['hash']));
    }

    private function sign(array $payload): string
    {
        unset($payload['hash']);
        $payload = array_filter($payload, fn ($value) => $value !== '' && $value !== null);
        ksort($payload);

        return md5(implode('&', array_map(fn ($key) => $key.'='.(string) $payload[$key], array_keys($payload))).config('payments.hupijiao.secret'));
    }

    public function assertConfigured(): void
    {
        if (! filled(config('payments.hupijiao.appid')) || ! filled(config('payments.hupijiao.secret')) || ! str_starts_with((string) config('payments.hupijiao.url'), 'https://')) {
            $this->fail('虎皮椒商户尚未配置。');
        }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['gateway' => $message]);
    }

    public function code(): string
    {
        return 'hupijiao_v3';
    }

    public function displayName(): string
    {
        return '虎皮椒 V3';
    }
}
